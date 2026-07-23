<?php

namespace App\Jobs;

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
    use Queueable;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 600;

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
            $this->log()->warning('RecoverProductionOrderNumbersProto: ya hay una recuperación en curso, se omite esta ejecución');
            return;
        }

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

            if ($probesSent > 0) {
                $this->logInforTableSnapshot();
                $this->executeInforProcedure();
                sleep(self::FSO_LOOKUP_DELAY_SECONDS);
            }

            // Fase 2: ya con el procedimiento corrido (o si ya venían sondas
            // pendientes de una corrida anterior), consultamos FSO por cada
            // registro para recuperar el número de orden.
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

                    $this->log()->info('RecoverProductionOrderNumbersProto: número de orden recuperado', [
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
            $this->log()->error('FALLO CRÍTICO en RecoverProductionOrderNumbersProto: ' . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Registros de producción sin número de orden que ya iniciaron/terminaron
     * producción y siguen a la espera de que Infor genere su shop order.
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
            // Solo registros que nunca se han sincronizado. Los que ya se enviaron
            // a Infor con cantidad real (synced_to_infor=true) antes de este cambio
            // y se quedaron sin shop_order_number quedan fuera de este Job a propósito:
            // no se les puede mandar otra sonda con qty=1 sin arriesgar duplicar/alterar
            // la cantidad ya registrada en FSO. Requieren revisión/backfill manual aparte.
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
     * Verifica si ya se envió una sonda (YFSORD vacío) para este registro,
     * para no volver a mandarla y duplicar la cantidad en Infor.
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
     * Inserta una sonda con cantidad mínima (1) en YF013 solo para que Infor
     * genere el número de orden. Las cantidades reales las envía
     * SyncProductionRecordsProto una vez que el registro ya tenga shop_order_number.
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
     * Consulta FSO en busca del número de orden (SORD) asignado a la sonda.
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

        return trim($fso->SORD);
    }

    /**
     * Registra en el log todo el contenido de la tabla YF013 antes de ejecutar
     * el programa de Infor, para poder rastrear registros duplicados
     */
    protected function logInforTableSnapshot()
    {
        $rows = YF013Proto::query()->get();

        $this->log()->info("RecoverProductionOrderNumbersProto: contenido de LX834FU02.YF013 antes de ejecutar el programa ({$rows->count()} registros)");

        foreach ($rows as $index => $row) {
            $this->log()->info(sprintf(
                'YF013 Proto [%d] | WorkCenter: %s (%s) | Orden: %s | Parte: %s | Fecha: %s | Turno: %s | Inicio: %s | Fin: %s | Plan: %s | Prod: %s | Scrap: %s | Creado: %s %s por %s',
                $index + 1,
                trim($row->YFWRKC ?? ''),
                trim($row->YFWRKN ?? ''),
                trim($row->YFSORD ?? ''),
                trim($row->YFPROD ?? ''),
                trim($row->YFRDTE ?? ''),
                trim($row->YFSHFT ?? ''),
                trim($row->YFSTIM ?? ''),
                trim($row->YFETIM ?? ''),
                $row->YFQPLA ?? '',
                $row->YFQPRO ?? '',
                $row->YFQSCR ?? '',
                trim($row->YFCRDT ?? ''),
                trim($row->YFCRTM ?? ''),
                trim($row->YFCRUS ?? '')
            ));
        }
    }

    /**
     * Ejecuta el programa almacenado en Infor para procesar la tabla puente YF013
     */
    protected function executeInforProcedure()
    {
        $dsn = "Driver={Client Access ODBC Driver (32-bit)};System=192.168.200.7;Uid=LXSECOFR;Pwd=LXSECOFR";

        $conn = odbc_connect($dsn, "", "");

        if (!$conn) {
            throw new Exception("Fallo de conexión ODBC: " . odbc_errormsg());
        }

        $result = @odbc_exec($conn, "CALL LX834OU02.YSF013C");

        if (!$result) {
            $error = odbc_errormsg($conn);
            odbc_close($conn);
            throw new Exception("Error en el procedimiento Infor: " . $error);
        }

        odbc_close($conn);
    }

    /**
     * Registra los resultados de la recuperación
     */
    protected function logResults($recoveredCount, $errorCount, $errors)
    {
        if ($recoveredCount > 0) {
            $this->log()->info("RecoverProductionOrderNumbersProto recuperó $recoveredCount número(s) de orden");
        }

        if ($errorCount > 0) {
            $this->log()->warning("RecoverProductionOrderNumbersProto terminó con $errorCount errores. Detalle: " . implode(', ', $errors));
        }
    }

    /**
     * Método opcional para manejar fallos del job
     */
    public function failed(Exception $exception)
    {
        $this->log()->error('El Job RecoverProductionOrderNumbersProto ha fallado definitivamente: ' . $exception->getMessage());
    }
}
