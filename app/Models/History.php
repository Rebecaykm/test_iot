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
     *
     */
    public static function getProductionHistory($workCenter, Shift $shift, $startDateTime, $endDateTime)
    {
        return History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
            ->join('production_records', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'shifts.id', '=', 'production_records.shift_id')
            ->where('shifts.id', '=', $shift->id)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->whereBetween('histories.created_at', [$startDateTime, $endDateTime])
            ->orderBy('work_centers.number', 'asc')
            ->orderBy('histories.created_at', 'asc')
            ->select(
                'production_records.id AS production_id',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'production_records.planned_quantity AS planned_quantity',
                'histories.created_at',
                'histories.quantity',
            )
            ->get();
    }
}
