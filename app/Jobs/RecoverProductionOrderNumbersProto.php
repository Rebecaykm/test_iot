<?php

namespace App\Jobs;

use App\Jobs\Concerns\CallsInforProcedure;
use App\Models\FSOProto;
use App\Models\InforSyncSetting;
use App\Models\ProductionRecord;
use App\Models\YF013Proto;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverProductionOrderNumbersProto implements ShouldQueue
{
    use Queueable, CallsInforProcedure;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 600;

    /** Espera a que Infor procese YF013 antes de consultar FSO */
    protected const FSO_LOOKUP_DELAY_SECONDS = 120;

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
        $lock = Cache::lock('recover-production-order-numbers-proto', $this->timeout + 30);

        if (!$lock->get()) {
            $this->log()->warning('[OMITIDO] RecoverProductionOrderNumbersProto: ya hay una recuperación en curso, se omite esta ejecución');
            return;
        }

        $this->log()->info('[INICIO] RecoverProductionOrderNumbersProto', [
            'Job' => 'RecoverProductionOrderNumbersProto',
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

            $errorCount = 0;
            $errors = [];

            $probesSent = 0;

            foreach ($productionRecords as $record) {
                try {
                    $plannedDateFormatted = $this->plannedDateFor($record);

                    if (!$this->probeExistsInInfor($record, $plannedDateFormatted)) {
                        $this->sendProbe($record, $plannedDateFormatted);
                        $probesSent++;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "ID {$record->id}: " . $e->getMessage();
                }
            }

            $this->log()->info("[YF013 - INSERT] RecoverProductionOrderNumbersProto: $probesSent sonda(s) nueva(s) insertada(s) en YF013");

            if ($probesSent > 0) {
                $this->logInforTableSnapshot();
                $this->callInforProcedure('LX834OU02.YSF013C');
                sleep(self::FSO_LOOKUP_DELAY_SECONDS);
            }

            $recoveredCount = 0;

            foreach ($productionRecords as $record) {
                try {
                    $plannedDateFormatted = $this->plannedDateFor($record);
                    $orderNumber = $this->fetchOrderNumberFromFso($record, $plannedDateFormatted);

                    if ($orderNumber === null) {
                        continue;
                    }

                    ProductionRecord::where('id', $record->id)->update([
                        'shop_order_number' => $orderNumber,
                    ]);

                    $this->log()->info('[ORDEN RECUPERADA] RecoverProductionOrderNumbersProto: número de orden recuperado', [
                        'Estación' => $record->work_name,
                        'Número de parte' => $record->part_number,
                        'Número de orden' => $orderNumber,
                        'Fecha planeada' => $plannedDateFormatted,
                        'Turno planeado' => $record->shift_abbreviation,
                        'Cantidad planeada' => $record->planned_quantity,
                        'Cantidad producida' => $record->produced_quantity,
                    ]);

                    $recoveredCount++;
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "ID {$record->id}: " . $e->getMessage();
                }
            }

            $this->logResults($recoveredCount, $errorCount, $errors);
        } catch (Exception $e) {
            $this->log()->error('[FALLO] FALLO CRÍTICO en RecoverProductionOrderNumbersProto: ' . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Registros elegibles para recuperar su número de orden
     */
    protected function getEligibleProductionRecords(array $workCenterNumbers)
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id',
                'production_records.planned_quantity',
                'production_records.produced_quantity',
                'production_records.planned_date',
                'production_records.production_start',
                'production_records.production_end',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'shifts.abbreviation AS shift_abbreviation',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->whereNull('production_records.shop_order_number')
            // Excluye legacy ya sincronizados sin orden (requieren backfill manual, no sonda)
            ->where('production_records.synced_to_infor', false)
            ->where('production_records.produced_quantity', '>', 0)
            // ->where('statuses.name', 'Detenido')
            ->whereIn('work_centers.number', $workCenterNumbers)
            ->whereBetween('production_records.planned_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->get();
    }

    protected function plannedDateFor($record): string
    {
        return $record->planned_date ? Carbon::parse($record->planned_date)->format('Ymd') : '';
    }

    /**
     * true si ya hay una sonda pendiente (YFSORD vacío) para este registro
     */
    protected function probeExistsInInfor($record, string $plannedDateFormatted): bool
    {
        return YF013Proto::query()
            ->where('YFWRKC', $record->work_number ?? '')
            ->where('YFSORD', '')
            ->where('YFRDTE', $plannedDateFormatted)
            ->where('YFSHFT', $record->shift_abbreviation ?? '')
            ->where('YFPROD', $record->part_number ?? '')
            ->count() > 0;
    }

    /**
     * Inserta sonda (qty=1) en YF013 para generar el número de orden
     */
    protected function sendProbe($record, string $plannedDateFormatted): void
    {
        $now = Carbon::now();

        $productionStart = $record->production_start
            ? Carbon::parse($record->production_start)
            : null;

        $productionEnd = $record->production_end
            ? Carbon::parse($record->production_end)
            : null;

        YF013Proto::query()->insert([
            'YFWRKC' => $record->work_number ?? '',
            'YFWRKN' => $record->work_name ?? '',
            'YFRDTE' => $plannedDateFormatted,
            'YFSHFT' => $record->shift_abbreviation ?? '',
            'YFPPNO' => '',
            'YFSORD' => '',
            'YFPROD' => $record->part_number ?? '',
            'YFSTIM' => $productionStart ? $productionStart->format('Hi') : '',
            'YFETIM' => $productionEnd ? $productionEnd->format('Hi') : '',
            'YFSDT' => $productionStart ? $productionStart->format('YmdHi') : '',
            'YFEDT' => $productionEnd ? $productionEnd->format('YmdHi') : '',
            'YFQPLA' => 1,
            'YFQPRO' => 1,
            'YFQSCR' => 0,
            'YFSCRE' => '',
            'YFCRDT' => $now->format('Ymd'),
            'YFCRTM' => $now->format('His'),
            'YFCRUS' => 'IOT',
        ]);
    }

    /**
     * Busca en FSO el número de orden (SORD) de la sonda
     */
    protected function fetchOrderNumberFromFso($record, string $plannedDateFormatted): ?string
    {
        $fso = FSOProto::query()
            ->where(DB::raw('TRIM(SPROD)'), $record->part_number ?? '')
            ->where('SRDTE', $plannedDateFormatted)
            ->where('SOCNO', $record->shift_abbreviation ?? '')
            ->where('SQREQ', 1)
            ->first();

        if (!$fso || !trim((string) $fso->SORD)) {
            return null;
        }

        $orderNumber = trim($fso->SORD);

        $this->log()->info('[FSO - CONSULTA] RecoverProductionOrderNumbersProto: registro encontrado en FSO', [
            'Parte' => trim($fso->SPROD ?? ''),
            'Fecha' => trim((string) ($fso->SRDTE ?? '')),
            'Turno' => trim($fso->SOCNO ?? ''),
            'Cantidad planeada (SQREQ)' => $fso->SQREQ,
            'Orden encontrada (SORD)' => $orderNumber,
        ]);

        return $orderNumber;
    }

    /**
     * Vuelca YF013 al log antes de correr el procedimiento
     */
    protected function logInforTableSnapshot()
    {
        $lines = YF013Proto::snapshotLines();

        $this->log()->info('[YF013 - SNAPSHOT] RecoverProductionOrderNumbersProto: contenido de LX834FU02.YF013 (' . count($lines) . ' registros)');

        foreach ($lines as $line) {
            $this->log()->info($line);
        }
    }

    /**
     * Registra los resultados de la recuperación
     */
    protected function logResults($recoveredCount, $errorCount, $errors)
    {
        $this->log()->info("[RESULTADO] RecoverProductionOrderNumbersProto: $recoveredCount número(s) de orden recuperado(s), $errorCount error(es)");

        if ($errorCount > 0) {
            $this->log()->warning("[RESULTADO] RecoverProductionOrderNumbersProto terminó con $errorCount errores. Detalle: " . implode(', ', $errors));
        }
    }

    /**
     * Fallo definitivo del job
     */
    public function failed(Exception $exception)
    {
        $this->log()->error('[FALLO] El Job RecoverProductionOrderNumbersProto ha fallado definitivamente: ' . $exception->getMessage());
    }
}
