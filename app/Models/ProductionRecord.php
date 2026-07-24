<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number_id',
        'planned_quantity',
        'produced_quantity',
        'scrap_quantity',
        'planned_date',
        'production_start',
        'production_end',
        'shift_id',
        'status_id',
        'shop_order_number',
        'synced_to_infor',
        'synced_at'
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'produced_quantity' => 'integer',
        'scrap_quantity' => 'integer',
        'synced_to_infor' => 'boolean',
    ];

    /**
     * [YFQPLA, YFQPRO] a enviar a YF013, compensando la sonda (qty=1) del
     * Job de recuperación de orden.
     */
    public function inforQuantities(): array
    {
        $producedNet = $this->produced_quantity - ($this->scrap_quantity ?? 0);

        if ((int) $this->planned_quantity === 0) {
            return [1, max(0, $producedNet - 1)];
        }

        return [$this->planned_quantity, $producedNet];
    }

    /**
     * Relación con Shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Relación con Status
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Relación con PartNumber
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    /**
     * Relación con ProductionSequence
     */
    public function sequences()
    {
        return $this->hasMany(ProductionSequence::class);
    }

    /**
     * Método optimizado para obtener registros de producción con eficiencia
     */
    public static function getWorkCenterProductionRecord(string $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
                'part_numbers.efficiency as efficiency', // Agregar eficiencia
                'part_numbers.production_order as production_order',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
                'production_records.production_start AS production_start'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->where('statuses.id', 7)
            ->orderBy('part_numbers.production_order', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('production_records.production_end', 'desc')
            ->get();
    }

    /**
     *
     */
    public static function store(
        $partNumberId,
        $plannedQuantity,
        $plannedDate,
        $shiftId = null,
        $shopOrderNumber = null,
    ) {
        $plannedQuantity = (int) $plannedQuantity;

        $status = Status::where('name', 'LIKE', 'Pendiente')->first();

        $productionPlan = ProductionRecord::query()->where([['part_number_id', $partNumberId], ['planned_date', $plannedDate], ['shift_id', $shiftId], ['shop_order_number', $shopOrderNumber]])->first();

        if ($productionPlan === null) {
            return ProductionRecord::create([
                'part_number_id' => $partNumberId,
                'planned_quantity' => $plannedQuantity,
                'planned_date' => $plannedDate,
                'shift_id' => $shiftId,
                'status_id' => $status->id,
                'shop_order_number' => $shopOrderNumber,
                'synced_to_infor' => false,
            ]);
        }

        return $productionPlan;
    }

    /**
     * Método para obtener registros con cache para mejor performance
     */
    public static function getCachedWorkCenterProductionRecord(string $workCenter, int $shiftId, $now): Collection
    {
        $cacheKey = "production_records_{$workCenter}_{$shiftId}_{$now->toDateString()}";

        return cache()->remember($cacheKey, 300, function () use ($workCenter, $shiftId, $now) {
            return self::getWorkCenterProductionRecord($workCenter, $shiftId, $now);
        });
    }

    /**
     * Método para limpiar cache cuando hay actualizaciones
     */
    public static function clearProductionCache(string $workCenter, int $shiftId, $date)
    {
        $cacheKey = "production_records_{$workCenter}_{$shiftId}_{$date}";
        cache()->forget($cacheKey);
    }

    public static function getProductionRecords(string $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.id AS part_number_id',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
                'part_numbers.production_order as production_order', // Agregamos el campo de orden
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
                'production_records.production_start AS production_start'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->orderBy('part_numbers.production_order', 'asc')
            // ->orderBy('shifts.start_time', 'asc')
            // ->orderBy('production_records.planned_date', 'asc')
            // ->orderBy('production_records.production_end', 'desc')
            ->get();
    }

    /**
     * Obtiene los registros de producción de un turno con su ventana de tiempo
     * (production_start / production_end) para dibujar una línea de tiempo (Gantt).
     * Solo trae los registros que ya iniciaron producción.
     */
    public static function getShiftProductionTimeline(string $workCenter, int $shiftId, $date): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_order as production_order',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.production_start AS production_start',
                'production_records.production_end AS production_end',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->where('production_records.planned_date', \Carbon\Carbon::parse($date)->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->whereNotNull('production_records.production_start')
            ->orderBy('production_records.production_start', 'asc')
            ->get();
    }

    /**
     * Plan del turno: TODAS las partes planeadas (hayan iniciado o no), agregadas
     * por número de parte. Se usa para dibujar la barra de "plan" en la línea de
     * tiempo, secuenciando por production_order y calculando el tiempo a partir de
     * production_rate (piezas/hora; en estampado es SPM y se convierte aparte).
     */
    public static function getShiftPlannedSchedule(string $workCenter, int $shiftId, $date): Collection
    {
        return ProductionRecord::query()
            ->select([
                'part_numbers.id AS part_number_id',
                'part_numbers.number AS part_number',
                'part_numbers.production_order AS production_order',
                'part_numbers.production_rate AS production_rate',
                'part_numbers.efficiency AS efficiency',
                DB::raw('SUM(production_records.planned_quantity) AS planned_quantity'),
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->where('production_records.planned_date', \Carbon\Carbon::parse($date)->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->groupBy(
                'part_numbers.id',
                'part_numbers.number',
                'part_numbers.production_order',
                'part_numbers.production_rate',
                'part_numbers.efficiency'
            )
            // Solo partes que tuvieron cantidad planeada o producida en el turno
            ->havingRaw('SUM(production_records.planned_quantity) > 0 OR SUM(production_records.produced_quantity) > 0')
            ->get();
    }

    /**
     * Obtiene los registros de producción de un work center en un rango de fechas
     * (p. ej. una semana de domingo a sábado), con plan y producido por registro.
     * Se usa para la tabla semanal de cumplimiento de producción.
     */
    public static function getWeeklyProductionRecords(string $workCenter, $startDate, $endDate): Collection
    {
        return ProductionRecord::query()
            ->select([
                'work_centers.name AS work_name',
                'production_records.shop_order_number AS order_number',
                'part_numbers.number AS part_number',
                'part_numbers.production_order AS production_order',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift',
                'shifts.name AS shift_name',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->whereBetween('production_records.planned_date', [
                \Carbon\Carbon::parse($startDate)->toDateString(),
                \Carbon\Carbon::parse($endDate)->toDateString(),
            ])
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.abbreviation', 'asc')
            ->orderBy('part_numbers.number', 'asc')
            ->get();
    }

    /**
     * Método para obtener el siguiente part number que debe producirse
     * basado en el orden de producción
     */
    public static function getNextPartNumberToProduceByOrder(string $workCenter, int $shiftId, $now)
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'part_numbers.number AS part_number',
                'part_numbers.production_order as production_order',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'statuses.name AS status_name'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->whereIn('statuses.name', ['Pendiente', 'En Progreso']) // Solo pendientes o en proceso
            ->orderBy('part_numbers.production_order', 'asc')
            ->first();
    }
}
