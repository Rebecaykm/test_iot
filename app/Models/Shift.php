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
     * Obtener el turno actual basado en la hora
     */
    public static function getShift($now): ?Shift
    {
        $currentTime = $now->format('H:i:s');

        return Shift::query()
            ->where(function ($query) use ($currentTime) {
                // Turno diurno: 08:00 - 20:00
                $query->where('abbreviation', 'D')
                    ->where('start_time', '<=', $currentTime)
                    ->where('end_time', '>', $currentTime);
            })
            ->orWhere(function ($query) use ($currentTime) {
                // Turno nocturno: 20:00 - 08:00 (cruza medianoche)
                $query->where('abbreviation', 'N')
                    ->where(function ($nestedQuery) use ($currentTime) {
                        // Primera parte: 20:00 - 23:59:59
                        $nestedQuery->where('start_time', '<=', $currentTime)
                            ->where('start_time', '>=', '20:00:00');
                    })
                    ->orWhere(function ($nestedQuery) use ($currentTime) {
                        // Segunda parte: 00:00:00 - 08:00
                        $nestedQuery->where('abbreviation', 'N')
                            ->where('end_time', '>', $currentTime)
                            ->where('end_time', '<=', '08:00:00');
                    });
            })
            ->first();
    }

    /**
     * Obtener rango de fecha y hora del turno
     */
    public static function getShiftDateTimeRange(Shift $shift, $referenceDate)
    {
        $date = Carbon::parse($referenceDate);

        if ($shift->abbreviation === 'D') {
            // Turno diurno: 08:00 - 20:00 del mismo día
            $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $date->copy()->setTimeFromTimeString($shift->end_time);

        } else { // Turno nocturno
            // Turno nocturno: 20:00 de un día - 08:00 del día siguiente
            $startDateTime = $date->copy()->setTimeFromTimeString($shift->start_time);
            $endDateTime = $date->copy()->addDay()->setTimeFromTimeString($shift->end_time);
        }

        return (object)[
            'shift' => $shift->abbreviation,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime
        ];
    }

    /**
     * Encontrar el turno anterior (función original mantenida por compatibilidad)
     */
    public static function findPreviousShift(Shift $currentShift)
    {
        if (!$currentShift) return null;

        $shifts = Shift::orderBy('end_time', 'desc')->get();

        $previousShift = null;
        foreach ($shifts as $shift) {
            if ($shift->end_time <= $currentShift->start_time && $shift->id !== $currentShift->id) {
                $previousShift = $shift;
                break;
            }
        }

        return $previousShift;
    }
}
