<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class History extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number_id',
        'quantity',
        'sequence',
        'shop_order_number',
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
    // public static function getProductionHistory($workCenter, Shift $shift, $startDateTime, $endDateTime)
    // {
    //     return History::query()
    //         ->select(
    //             [
    //                 'production_records.id AS production_id',
    //                 'work_centers.name AS work_name',
    //                 'part_numbers.number AS part_number',
    //                 'production_records.planned_quantity AS planned_quantity',
    //                 'histories.created_at',
    //                 'histories.quantity',
    //             ]
    //         )
    //         ->join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
    //         ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
    //         ->join('production_records', 'production_records.part_number_id', '=', 'part_numbers.id')
    //         ->join('shifts', 'shifts.id', '=', 'production_records.shift_id')
    //         ->where('shifts.id', '=', $shift->id)
    //         ->where('work_centers.name', 'LIKE', $workCenter)
    //         ->whereBetween('histories.created_at', [$startDateTime, $endDateTime])
    //         ->orderBy('work_centers.number', 'asc')
    //         ->orderBy('histories.created_at', 'asc')
    //         ->get();
    // }

    /**
     *
     */
    public static function getProductionHistory($workCenter, $startDateTime, $endDateTime)
    {
        $workCenterId = Cache::remember(
            "work_center_id:{$workCenter}",
            now()->addHours(6),
            fn () => WorkCenter::where('name', $workCenter)->value('id')
        );

        if (! $workCenterId) {
            return collect();
        }

        return History::query()
            ->select([
                'part_numbers.number AS part_number',
                'histories.quantity',
                'histories.created_at'
            ])
            ->join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->where('part_numbers.work_center_id', $workCenterId)
            ->whereBetween('histories.created_at', [
                $startDateTime->format('Y-m-d H:i:s'),
                $endDateTime->format('Y-m-d H:i:s')
            ])
            ->orderBy('histories.created_at', 'asc')
            ->get();
    }

    /**
     * Devuelve solo (quantity, created_at) — no incluye part_number.
     * Pensado para gráficas que solo necesitan agrupar por tiempo.
     */
    public static function getProducedTimeline($workCenter, $startDateTime, $endDateTime)
    {
        $workCenterId = Cache::remember(
            "work_center_id:{$workCenter}",
            now()->addHours(6),
            fn () => WorkCenter::where('name', $workCenter)->value('id')
        );

        if (! $workCenterId) {
            return collect();
        }

        return History::query()
            ->select(['histories.part_number_id', 'histories.quantity', 'histories.created_at'])
            ->whereIn(
                'histories.part_number_id',
                PartNumber::where('work_center_id', $workCenterId)->select('id')
            )
            ->whereBetween('histories.created_at', [
                $startDateTime->format('Y-m-d H:i:s'),
                $endDateTime->format('Y-m-d H:i:s')
            ])
            ->orderBy('histories.created_at', 'asc')
            ->get();
    }

    /**
     * Suma de piezas producidas en un rango, agrupada por part_number_id.
     * Devuelve [part_number_id => piezas]. Pensado para convertir a golpes
     * aplicando el divisor de cada part (ver PartNumber::getShotDivisorsByWorkCenter).
     */
    public static function getProducedQuantityByPart($workCenter, $startDateTime, $endDateTime): array
    {
        $workCenterId = Cache::remember(
            "work_center_id:{$workCenter}",
            now()->addHours(6),
            fn () => WorkCenter::where('name', $workCenter)->value('id')
        );

        if (! $workCenterId) {
            return [];
        }

        return History::query()
            ->whereIn(
                'histories.part_number_id',
                PartNumber::where('work_center_id', $workCenterId)->select('id')
            )
            ->whereBetween('histories.created_at', [
                $startDateTime->format('Y-m-d H:i:s'),
                $endDateTime->format('Y-m-d H:i:s')
            ])
            ->groupBy('histories.part_number_id')
            ->selectRaw('histories.part_number_id, SUM(histories.quantity) AS qty')
            ->pluck('qty', 'part_number_id')
            ->toArray();
    }

    /**
     * Suma la cantidad producida en un rango sin traer las filas a PHP.
     */
    public static function getTotalProducedQuantity($workCenter, $startDateTime, $endDateTime): int
    {
        $workCenterId = Cache::remember(
            "work_center_id:{$workCenter}",
            now()->addHours(6),
            fn () => WorkCenter::where('name', $workCenter)->value('id')
        );

        if (! $workCenterId) {
            return 0;
        }

        return (int) History::query()
            ->join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->where('part_numbers.work_center_id', $workCenterId)
            ->whereBetween('histories.created_at', [
                $startDateTime->format('Y-m-d H:i:s'),
                $endDateTime->format('Y-m-d H:i:s')
            ])
            ->sum('histories.quantity');
    }
}
