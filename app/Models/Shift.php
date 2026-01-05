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
     * Obtener todos los turnos disponibles
     */
    public static function getAllShifts()
    {
        return Shift::orderBy('start_time')->get();
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
     * Obtener el turno anterior al turno actual
     */
    public static function getPreviousShift(?Shift $currentShift): ?Shift
    {
        if (!$currentShift) return null;

        // Si el turno actual es diurno (D), el anterior es nocturno (N)
        if ($currentShift->abbreviation === 'D') {
            return Shift::where('abbreviation', 'N')->first();
        }

        // Si el turno actual es nocturno (N), el anterior es diurno (D)
        if ($currentShift->abbreviation === 'N') {
            return Shift::where('abbreviation', 'D')->first();
        }

        return null;
    }

    /**
     * Calcular la fecha correcta para el turno actual
     */
    public static function calculateCurrentShiftDate(?Shift $currentShift, Carbon $now): Carbon
    {
        if (!$currentShift) {
            return $now->copy();
        }

        $nowCopy = $now->copy();

        // Si el turno actual es diurno (08:00-20:00)
        if ($currentShift->abbreviation === 'D') {
            // Usar la fecha actual
            return $nowCopy;
        }

        // Si el turno actual es nocturno (20:00-08:00 del siguiente día)
        if ($currentShift->abbreviation === 'N') {
            // Verificar si estamos en la primera parte (20:00-23:59) o segunda parte (00:00-08:00) del turno nocturno
            $currentTime = $nowCopy->format('H:i');

            if ($currentTime >= '20:00') {
                // Estamos en la primera parte del turno nocturno (20:00-23:59)
                // Usar la fecha actual (el turno comenzó hoy)
                return $nowCopy;
            } else {
                // Estamos en la segunda parte del turno nocturno (00:00-08:00)
                // El turno comenzó el día anterior (restar un día para obtener la fecha de inicio)
                return $nowCopy->subDay();
            }
        }

        return $nowCopy;
    }

    /**
     * Calcular la fecha correcta para el turno anterior
     */
    public static function calculatePreviousShiftDate(?Shift $currentShift, Carbon $now): Carbon
    {
        if (!$currentShift) {
            return $now->copy();
        }

        $nowCopy = $now->copy();

        // Si el turno actual es diurno (08:00-20:00)
        if ($currentShift->abbreviation === 'D') {
            // El turno anterior es nocturno del día anterior (20:00 del día anterior - 08:00 del día actual)
            return $nowCopy->subDay();
        }

        // Si el turno actual es nocturno (20:00-08:00 del siguiente día)
        if ($currentShift->abbreviation === 'N') {
            // Verificar si estamos en la primera parte (20:00-23:59) o segunda parte (00:00-08:00) del turno nocturno
            $currentTime = $nowCopy->format('H:i');

            if ($currentTime >= '20:00') {
                // Estamos en la primera parte del turno nocturno (20:00-23:59)
                // El turno anterior es diurno del mismo día (08:00-20:00)
                return $nowCopy;
            } else {
                // Estamos en la segunda parte del turno nocturno (00:00-08:00)
                // El turno anterior es diurno del día anterior (08:00-20:00 del día anterior)
                return $nowCopy->subDay();
            }
        }

        return $nowCopy;
    }

    /**
     * Obtener información completa del turno actual con fechas calculadas
     */
    public static function getCurrentShiftInfo(Carbon $now): object
    {
        $currentShift = self::getShift($now);
        $currentDate = self::calculateCurrentShiftDate($currentShift, $now);
        $currentTimeRange = self::getShiftDateTimeRange($currentShift, $currentDate);

        return (object) [
            'shift' => $currentShift,
            'date' => $currentDate,
            'timeRange' => $currentTimeRange
        ];
    }

    /**
     * Obtener información completa del turno anterior con fechas calculadas
     */
    public static function getPreviousShiftInfo(Carbon $now): object
    {
        $currentShift = self::getShift($now);
        $previousShift = self::getPreviousShift($currentShift);
        $previousDate = self::calculatePreviousShiftDate($currentShift, $now);
        $previousTimeRange = self::getShiftDateTimeRange($previousShift, $previousDate);

        return (object) [
            'shift' => $previousShift,
            'date' => $previousDate,
            'timeRange' => $previousTimeRange
        ];
    }

    /**
     * Obtener rango de fecha y hora del turno
     */
    public static function getShiftDateTimeRange(?Shift $shift, $referenceDate)
    {
        if (!$shift) {
            return null;
        }

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
     * @deprecated Usar getPreviousShift() en su lugar
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
