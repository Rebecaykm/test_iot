<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number_id',
        'work_center_id',
        'production_order',
        'effective_date',
    ];

    /**
     * Relación con PartNumber
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    /**
     * Relación con WorkCenter
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     * Devuelve el production_order que estaba vigente para un part number en una
     * fecha dada (el último valor registrado en o antes de esa fecha). Si no hay
     * historial previo a esa fecha, devuelve null.
     */
    public static function orderOnDate(int $partNumberId, string $date): ?int
    {
        return static::query()
            ->where('part_number_id', $partNumberId)
            ->whereDate('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->value('production_order');
    }
}
