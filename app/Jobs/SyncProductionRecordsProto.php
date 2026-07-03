<?php

namespace App\Jobs;

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
    use Queueable;

    public $tries = 3;
    public $backoff = 60;
    public $timeout = 600;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Evita que dos ejecuciones (programada + manual, o solapadas) procesen
        // el mismo lote de registros antes de que cualquiera marque synced_to_infor.
        $lock = Cache::lock('sync-production-records-proto', $this->timeout + 30);

        if (!$lock->get()) {
            Log::warning('SyncProductionRecordsProto: ya hay una sincronización en curso, se omite esta ejecución');
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

            // Solo ejecutamos el procedimiento si hubo inserciones exitosas
            if ($successCount > 0) {
                $this->logInforTableSnapshot();
                $this->executeInforProcedure();
            }

            $this->logResults($successCount, $errorCount, $errors);
        } catch (Exception $e) {
            Log::error('FALLO CRÍTICO en SyncProductionRecordsProto: ' . $e->getMessage());
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
            Log::warning('SyncProductionRecordsProto: el registro ya existía en YF013, se omite el insert duplicado', [
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
                'YFQPLA' => $record->planned_quantity ?: $record->produced_quantity,
                'YFQPRO' => $record->produced_quantity - ($record->scrap_quantity ?? 0),
                'YFQSCR' => $record->scrap_quantity ?? 0,
                'YFSCRE' => ($record->scrap_quantity ?? 0) == 0 ? '' : 'RJ',
                'YFCRDT' => $now->format('Ymd'),
                'YFCRTM' => $now->format('His'),
                'YFCRUS' => 'IOT',
            ]);

        Log::info('SyncProductionRecordsProto', [
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
     * Verifica si el registro ya existe en la tabla puente de Infor YF013
     *
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
     * Registra en el log todo el contenido de la tabla YF013 antes de ejecutar
     * el programa de Infor, para poder rastrear registros duplicados
     */
    protected function logInforTableSnapshot()
    {
        $rows = YF013Proto::query()->get();

        Log::info("SyncProductionRecordsProto: contenido de LX834FU02.YF013 antes de ejecutar el programa ({$rows->count()} registros)");

        foreach ($rows as $index => $row) {
            Log::info(sprintf(
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
     * Ejecuta el programa almacenado en Infor
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
     * Registra los resultados de la sincronización
     */
    protected function logResults($successCount, $errorCount, $errors)
    {
        if ($errorCount > 0) {
            Log::warning("SyncProductionRecordsProto terminó con $errorCount errores. Detalle: " . implode(', ', $errors));
        }
    }

    /**
     * Método opcional para manejar fallos del job
     */
    public function failed(Exception $exception)
    {
        Log::error('El Job SyncProductionRecordsProto ha fallado definitivamente: ' . $exception->getMessage());
    }
}
