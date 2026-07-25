<?php

namespace App\Jobs;

use App\Jobs\Concerns\CallsInforProcedure;
use App\Models\InforSyncSetting;
use App\Models\ProductionRecord;
use App\Models\YF013Proto;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncProductionRecordsProto implements ShouldQueue
{
    use Queueable, CallsInforProcedure;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 600;

    public function __construct()
    {
        //
    }

    protected function log()
    {
        return Log::channel('infor_proto');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Evita ejecuciones simultáneas
        $lock = Cache::lock('sync-production-records-proto', $this->timeout + 30);

        if (!$lock->get()) {
            $this->log()->warning('[OMITIDO] SyncProductionRecordsProto: ya hay una sincronización en curso, se omite esta ejecución');
            return;
        }

        $this->log()->info('[INICIO] SyncProductionRecordsProto', [
            'Job' => 'SyncProductionRecordsProto',
            'Ambiente' => 'Proto',
            'Fecha y hora de ejecución' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $setting = InforSyncSetting::forEnvironment(InforSyncSetting::ENVIRONMENT_PROTO);

            if (!$setting || !$setting->enabled) {
                return;
            }

            $workCenterNumbers = $setting->workCenterNumbers();

            if (empty($workCenterNumbers)) {
                return;
            }

            $productionRecords = $this->getEligibleProductionRecords($workCenterNumbers);

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
                $this->callInforProcedure('LX834OU02.YSF013C');
            }

            $this->logResults($successCount, $errorCount, $errors);
        } catch (Exception $e) {
            $this->log()->error('[FALLO] FALLO CRÍTICO en SyncProductionRecordsProto: ' . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    protected function getEligibleProductionRecords(array $workCenterNumbers)
    {
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
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.synced_to_infor', false)
            ->where('production_records.produced_quantity', '>', 0)
            ->where('statuses.name', 'Detenido')
            // ->whereNotNull('production_records.shop_order_number')
            ->whereIn('work_centers.number', $workCenterNumbers)
            ->whereBetween('production_records.planned_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->get();
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
            $this->log()->warning('[YF013 - INSERT] SyncProductionRecordsProto: el registro ya existía en YF013, se omite el insert duplicado', [
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
        $inserted = YF013Proto::query()
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

        $this->log()->info('[YF013 - INSERT] SyncProductionRecordsProto: registro insertado en YF013', [
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
        return YF013Proto::query()
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
        $lines = YF013Proto::snapshotLines();

        $this->log()->info('[YF013 - SNAPSHOT] SyncProductionRecordsProto: contenido de LX834FU02.YF013 (' . count($lines) . ' registros)');

        foreach ($lines as $line) {
            $this->log()->info($line);
        }
    }

    /**
     * Registra los resultados de la sincronización
     */
    protected function logResults($successCount, $errorCount, $errors)
    {
        $this->log()->info("[RESULTADO] SyncProductionRecordsProto: $successCount registro(s) sincronizado(s), $errorCount error(es)");

        if ($errorCount > 0) {
            $this->log()->warning("[RESULTADO] SyncProductionRecordsProto terminó con $errorCount errores. Detalle: " . implode(', ', $errors));
        }
    }

    /**
     * Fallo definitivo del job
     */
    public function failed(Exception $exception)
    {
        $this->log()->error('[FALLO] El Job SyncProductionRecordsProto ha fallado definitivamente: ' . $exception->getMessage());
    }
}
