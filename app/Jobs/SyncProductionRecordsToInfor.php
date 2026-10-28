<?php

namespace App\Jobs;

use App\Jobs\Concerns\CallsInforProcedure;
use App\Models\InforSyncSetting;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Envía a la tabla de paso YF013 de Infor los registros Completado con orden
 * de las estaciones seleccionadas en /infor-sync. Live y Proto solo cambian
 * el ambiente, la tabla YF013, el canal de log y el procedimiento.
 */
abstract class SyncProductionRecordsToInfor implements ShouldQueue
{
    use Queueable, CallsInforProcedure;

    /**
     * Clases de parte que se reportan a Infor.
     */
    public const ITEM_CLASSES = ['M1', 'M2', 'M3', 'M4'];

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 600;

    /**
     * Ambiente de InforSyncSetting (live | proto).
     */
    abstract protected function environment(): string;

    /**
     * Modelo de la tabla YF013 del ambiente.
     *
     * @return class-string<Model>
     */
    abstract protected function inforModel(): string;

    abstract protected function logChannel(): string;

    /**
     * Procedimiento de Infor que procesa YF013 después de insertar; null si
     * no se llama.
     */
    abstract protected function inforProcedure(): ?string;

    protected function log()
    {
        return Log::channel($this->logChannel());
    }

    protected function jobName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Evita ejecuciones simultáneas
        $lock = Cache::lock('sync-production-records-' . $this->environment(), $this->timeout + 30);

        if (!$lock->get()) {
            $this->log()->warning("[OMITIDO] {$this->jobName()}: ya hay una sincronización en curso, se omite esta ejecución");
            return;
        }

        $this->log()->info("[INICIO] {$this->jobName()}", [
            'Job' => $this->jobName(),
            'Ambiente' => ucfirst($this->environment()),
            'Fecha y hora de ejecución' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $setting = InforSyncSetting::forEnvironment($this->environment());

            if (!$setting || !$setting->enabled) {
                return;
            }

            $workCenterNumbers = $setting->workCenterNumbers();

            if (empty($workCenterNumbers)) {
                return;
            }

            $productionRecords = $this->getEligibleProductionRecords($setting, $workCenterNumbers);

            if ($productionRecords->isEmpty()) {
                return;
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($productionRecords as $record) {
                try {
                    $result = $this->processRecord($record);

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "ID {$record->id}: " . $result['message'];
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "ID {$record->id}: " . $e->getMessage();
                }
            }

            // Solo si hubo inserciones exitosas
            if ($successCount > 0) {
                $this->logInforTableSnapshot();

                if ($this->inforProcedure() !== null) {
                    $this->callInforProcedure($this->inforProcedure());
                }
            }

            $this->logResults($successCount, $errorCount, $errors);
        } catch (Exception $e) {
            $this->log()->error("[FALLO] FALLO CRÍTICO en {$this->jobName()}: " . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function getEligibleProductionRecords(InforSyncSetting $setting, array $workCenterNumbers)
    {
        [$from, $to] = $this->sendWindow();

        return ProductionRecord::query()
            ->select([
                'production_records.id',
                'production_records.synced_to_infor',
                'production_records.synced_at',
                'production_records.scrap_quantity',
                'production_records.produced_quantity',
                'production_records.planned_quantity',
                'production_records.planned_date',
                'production_records.production_start',
                'production_records.production_end',
                'production_records.shop_order_number',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'shifts.abbreviation AS shift_abbreviation',
                'statuses.name AS status_name',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('item_classes', 'part_numbers.item_class_id', '=', 'item_classes.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.synced_to_infor', false)
            ->where('production_records.produced_quantity', '>', 0)
            ->where('statuses.name', 'Completado')
            ->whereNotNull('production_records.shop_order_number')
            ->where('part_numbers.is_obsolete', false)
            ->whereIn('item_classes.abbreviation', self::ITEM_CLASSES)
            ->whereIn('work_centers.number', $workCenterNumbers)
            // Solo desde la fecha de inicio de envío de cada estación
            ->tap(fn ($query) => $setting->applySyncFrom($query))
            ->whereBetween('production_records.planned_date', [$from, $to])
            ->get();
    }

    /**
     * Fechas planeadas que se envían: solo el mes operativo (el N antes de las
     * 08:00 es del día anterior). El día 1 en turno D también se envía el
     * último día del mes anterior, porque su turno N termina el 1 a las 08:00.
     *
     * @return array{0: string, 1: string}
     */
    protected function sendWindow(): array
    {
        $currentShift = Shift::getCurrentShiftInfo(Carbon::now());
        $operatingDate = $currentShift->date->copy()->startOfDay();

        $from = $operatingDate->copy()->startOfMonth();

        if ($operatingDate->day === 1 && $currentShift->shift?->abbreviation === 'D') {
            $from = $operatingDate->copy()->subDay();
        }

        return [
            ProductionRecord::applyHistoryFloor($from)->toDateString(),
            $operatingDate->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Procesa un registro individual
     */
    protected function processRecord($record)
    {
        $now = Carbon::now();

        $productionStart = $record->production_start
            ? Carbon::parse($record->production_start)
            : null;

        $productionEnd = $record->production_end
            ? Carbon::parse($record->production_end)
            : null;

        $plannedDateFormatted = $record->planned_date ? Carbon::parse($record->planned_date)->format('Ymd') : '';

        if ($this->existsInInfor($record, $plannedDateFormatted)) {
            $this->log()->warning("[YF013 - INSERT] {$this->jobName()}: el registro ya existía en YF013, se omite el insert duplicado", [
                'Production Record' => $record->id,
                'Shop order number' => $record->shop_order_number,
                'Work Center' => $record->work_number,
            ]);

            ProductionRecord::where('id', $record->id)->update([
                'synced_to_infor' => true,
                'synced_at' => $now,
            ]);

            return ['success' => true];
        }

        [$plannedToSend, $producedToSend] = $record->inforQuantities();

        // Intentar insertar en la tabla de paso YF013
        $inserted = $this->inforModel()::query()
            ->insert([
                'YFWRKC' => $record->work_number ?? '',
                'YFWRKN' => $record->work_name ?? '',
                'YFRDTE' => $plannedDateFormatted,
                'YFSHFT' => $record->shift_abbreviation ?? '',
                'YFPPNO' => '',
                'YFSORD' => $record->shop_order_number ?? '',
                'YFPROD' => $record->part_number ?? '',
                'YFSTIM' => $productionStart ? $productionStart->format('Hi') : '',
                'YFETIM' => $productionEnd ? $productionEnd->format('Hi') : '',
                'YFSDT' => $productionStart ? $productionStart->format('YmdHi') : '',
                'YFEDT' => $productionEnd ? $productionEnd->format('YmdHi') : '',
                'YFQPLA' => $plannedToSend,
                'YFQPRO' => $producedToSend,
                'YFQSCR' => $record->scrap_quantity ?? 0,
                'YFSCRE' => ($record->scrap_quantity ?? 0) == 0 ? '' : 'RJ',
                'YFCRDT' => $now->format('Ymd'),
                'YFCRTM' => $now->format('His'),
                'YFCRUS' => 'IOT',
            ]);

        $this->log()->info("[YF013 - INSERT] {$this->jobName()}: registro insertado en YF013", [
            'Shop order number' => $record->shop_order_number,
            'Work Center' => $record->work_number,
            'Part Number' => $record->part_number,
            'Planned Date' => $record->planned_date,
            'Planned Quantity' => $record->planned_quantity,
            'Produced Quantity' => $record->produced_quantity,
            'Scrap Quantity' => $record->scrap_quantity,
        ]);

        if ($inserted) {
            ProductionRecord::where('id', $record->id)->update([
                'synced_to_infor' => true,
                'synced_at' => $now,
            ]);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Fallo al insertar en YF013'];
    }

    /**
     * true si el registro ya existe en YF013 (evita insert duplicado)
     */
    protected function existsInInfor($record, string $plannedDateFormatted): bool
    {
        return $this->inforModel()::query()
            ->where('YFWRKC', $record->work_number ?? '')
            ->where('YFSORD', $record->shop_order_number ?? '')
            ->where('YFRDTE', $plannedDateFormatted)
            ->where('YFSHFT', $record->shift_abbreviation ?? '')
            ->where('YFPROD', $record->part_number ?? '')
            ->count() > 0;
    }

    /**
     * Vuelca YF013 al log antes de correr el procedimiento
     */
    protected function logInforTableSnapshot()
    {
        $model = $this->inforModel();
        $lines = $model::snapshotLines();

        $this->log()->info("[YF013 - SNAPSHOT] {$this->jobName()}: contenido de " . (new $model)->getTable() . ' (' . count($lines) . ' registros)');

        foreach ($lines as $line) {
            $this->log()->info($line);
        }
    }

    /**
     * Registra los resultados de la sincronización
     */
    protected function logResults($successCount, $errorCount, $errors)
    {
        $this->log()->info("[RESULTADO] {$this->jobName()}: $successCount registro(s) sincronizado(s), $errorCount error(es)");

        if ($errorCount > 0) {
            $this->log()->warning("[RESULTADO] {$this->jobName()} terminó con $errorCount errores. Detalle: " . implode(', ', $errors));
        }
    }

    /**
     * Fallo definitivo del job
     */
    public function failed(Exception $exception)
    {
        $this->log()->error("[FALLO] El Job {$this->jobName()} ha fallado definitivamente: " . $exception->getMessage());
    }
}
