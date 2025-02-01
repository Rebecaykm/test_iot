<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'abbreviation',
        'name',
        'start_time',
        'end_time',
        'description'
    ];

    /**
     * Relación con ProductionRecord
     */
    public function productionRecord(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'shift_id');
    }

    /**
     * Obtener el turno
     */
    public static function getShift($now): ?Shift
    {
        return Shift::query()
            ->where(function ($query) use ($now) {
                $query->where('abbreviation', 'D') // Turno diurno
                    ->whereTime('start_time', '<=', $now)
                    ->whereTime('end_time', '>', $now);
            })
            ->orWhere(function ($query) use ($now) {
                $query->where('abbreviation', 'N') // Turno nocturno
                    ->where(function ($nestedQuery) use ($now) {
                        $nestedQuery->whereTime('start_time', '<=', $now)
                            ->orWhereTime('end_time', '>=', $now);
                    });
            })
            ->first();
    }

    /**
     * Obtener rango del turno
     */
    public static function getShiftDateTimeRange(Shift $shift, $now)
    {
        $startTime = $now->copy()->setTimeFromTimeString($shift->start_time);

        if ($shift->abbreviation === 'N') {
            if ($now->greaterThan(Carbon::today())) {
                $startDate = $startTime->subDay();
            } else {
                $startDate = $startTime;
            }
            $endDate = $startTime->copy()->addDay()->setTimeFromTimeString($shift->end_time);
        } else {
            $startDate = $startTime;
            $endDate = $now->copy()->setTimeFromTimeString($shift->end_time);
        }

        return (object) [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
}
