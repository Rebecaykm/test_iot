<?php

namespace App\Jobs;

use App\Models\ProductionRecord;
use App\Models\WorkCenter;
use App\Models\YF013;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductionRecords implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando sincronización masiva con Infor');

        try {
            $productionRecords = $this->getEligibleProductionRecords();

            if ($productionRecords->isEmpty()) {
                Log::info('No hay registros para sincronizar');
                return;
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            Log::info("Encontrados {$productionRecords->count()} registros para sincronizar");

            // 2. Procesar cada registro
            foreach ($productionRecords as $record) {
                try {
                    $result = $this->processRecord($record);

                    if ($result['success']) {
                        $successCount++;
                        Log::info("Registro {$record->id} sincronizado exitosamente");
                    } else {
                        $errorCount++;
                        $errors[] = "Registro {$record->id}: {$result['message']}";
                        Log::error("Error sincronizando registro {$record->id}: {$result['message']}");
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Registro {$record->id}: {$e->getMessage()}";
                    Log::error("Excepción en registro {$record->id}: " . $e->getMessage());
                }
            }

            // 3. Ejecutar procedimiento almacenado en Infor si hay éxitos
            if ($successCount > 0) {
                $this->executeInforProcedure();
            }

            // 4. Log de resultados
            $this->logResults($successCount, $errorCount, $errors);

        } catch (Exception $e) {
            Log::error('Error en el job de sincronización masiva: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los registros elegibles para sincronización
     */
    protected function getEligibleProductionRecords()
    {
        $workCentersArray = WorkCenter::with('line')
            ->whereHas('line', function ($q) {
                $q->where('name', 'Miniceldas');
            })
            ->pluck('number')
            ->toArray();

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
            ->whereIn('work_centers.number', $workCentersArray)
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

        // Insertar en YF013
        $inserted = YF013::query()
            ->insert([
                'YFWRKC' => $record->work_number ?? '',
                'YFWRKN' => $record->work_name ?? '',
                'YFRDTE' => $record->planned_date
                    ? Carbon::parse($record->planned_date)->format('Ymd')
                    : '',
                'YFSHFT' => $record->shift_abbreviation ?? '',
                'YFPPNO' => '',
                'YFSORD' => $record->shop_order_number ?? '',
                'YFPROD' => $record->part_number ?? '',
                'YFSTIM' => $productionStart ? $productionStart->format('Hi') : '',
                'YFETIM' => $productionEnd ? $productionEnd->format('Hi') : '',
                'YFSDT' => $productionStart ? $productionStart->format('YmdHi') : '',
                'YFEDT' => $productionEnd ? $productionEnd->format('YmdHi') : '',
                'YFQPLA' => $record->planned_quantity ?: $record->produced_quantity,
                'YFQPRO' => $record->produced_quantity - $record->scrap_quantity,
                'YFQSCR' => $record->scrap_quantity ?? 0,
                'YFSCRE' => ($record->scrap_quantity ?? 0) == 0 ? '' : 'RJ',
                'YFCRDT' => $now->format('Ymd'),
                'YFCRTM' => $now->format('His'),
                'YFCRUS' => '',
            ]);

        if ($inserted) {
            ProductionRecord::where('id', $record->id)->update([
                'synced_to_infor' => true,
                'synced_at' => $now,
            ]);

            return [
                'success' => true,
                'message' => 'Sincronizado exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al insertar en YF013'
        ];
    }

    /**
     * Ejecuta el programa almacenado en Infor
     */
    protected function executeInforProcedure()
    {
        try {
            Log::info('Iniciando ejecución del programa en Infor');

            $conn = odbc_connect(
                "Driver={Client Access ODBC Driver (32-bit)};System=192.168.200.7;Uid=LXSECOFR;Pwd=LXSECOFR",
                "",
                ""
            );

            if ($conn === false) {
                $error = odbc_errormsg();
                throw new Exception("Error al conectar con la base de datos Infor: " . $error);
            }

            $query = "CALL LX834OU.YSF013C";
            $result = odbc_exec($conn, $query);

            if ($result) {
                Log::info("Procedimiento LX834OU.YSF013C ejecutado con éxito");

                // Obtener información del resultado si es necesario
                odbc_free_result($result);
            } else {
                $error = odbc_errormsg($conn);
                throw new Exception("Error en el procedimiento: " . $error);
            }

            odbc_close($conn);
            Log::info('Conexión a Infor cerrada correctamente');

        } catch (Exception $e) {
            Log::error("Error ejecutando procedimiento Infor: " . $e->getMessage());
            throw $e; // Relanzar para que el job falle si esto es crítico
        }
    }

    /**
     * Registra los resultados de la sincronización
     */
    protected function logResults($successCount, $errorCount, $errors = [])
    {
        $message = "Sincronización masiva completada. ";
        $message .= "Éxitos: {$successCount}, Errores: {$errorCount}";

        if ($successCount > 0) {
            Log::info($message);
        } else {
            Log::warning($message);
        }

        if ($errorCount > 0 && !empty($errors)) {
            Log::error('Errores detallados: ' . implode(' | ', array_slice($errors, 0, 10)));

            // Si hay más de 10 errores, agregar conteo adicional
            if (count($errors) > 10) {
                Log::error('... y ' . (count($errors) - 10) . ' errores más');
            }
        }
    }

    /**
     * Método opcional para manejar fallos del job
     */
    public function failed(Exception $exception)
    {
        Log::error('Job SyncProductionRecords falló: ' . $exception->getMessage());

        // Aquí puedes notificar a los administradores, etc.
        // Mail::to('admin@example.com')->send(new JobFailedMail($exception));
    }
}
