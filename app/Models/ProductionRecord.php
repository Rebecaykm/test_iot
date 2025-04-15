<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status_id'
    ];

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
     *
     */
    public static function store($partNumberId, $plannedQuantity, $plannedDate, $shiftId)
    {
        $status = Status::where('name', 'LIKE', 'Pendiente')->first();

        $productionPlan = ProductionRecord::query()->where([['part_number_id', $partNumberId], ['planned_quantity', $plannedQuantity], ['planned_date', $plannedDate], ['shift_id', $shiftId]])->first();

        if ($productionPlan === null) {
            return ProductionRecord::create([
                'part_number_id' => $partNumberId,
                'planned_quantity' => $plannedQuantity,
                'planned_date' => $plannedDate,
                'shift_id' => $shiftId,
                'status_id' => $status->id
            ]);
        }
    }

    /**
     * Obtener los registros de producción por WorkCenter
     */
    public static function getWorkCenterProductionRecord(String $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
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
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('production_records.production_end', 'desc')
            ->get();
    }

    public static function getPressProductionRecord(String $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
//                'statuses.name AS status_name',
                'production_records.production_start AS production_start'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
//            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            // ->where('statuses.id', 7)
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('production_records.production_end', 'desc')
            ->get();
    }
}
