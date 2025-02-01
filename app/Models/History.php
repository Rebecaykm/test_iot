<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number_id',
        'quantity',
        'sequence',
        'created_at',
        'updated_at'
    ];

    /**
     * Relación con PartNumber
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    /**
     * Obtener los registros de producción por WorkCenter
     */
    public static function getWorkCenterProductionRecord($workCenter, $shiftId, $startDate, $endDate)
    {
        return History::query()
            ->join('part_numbers', 'histories.part_number_id', '=', 'part_numbers.id')  // Corregido el `join`
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('production_records', 'part_numbers.id', '=', 'production_records.part_number_id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->where('shifts.id', '=', $shiftId)
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->whereBetween('histories.created_at', [$startDate, $endDate])
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name'
            ])
            ->get();
    }
}
