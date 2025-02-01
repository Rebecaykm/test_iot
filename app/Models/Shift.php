<?php

namespace App\Models;

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
     * Obtener el turno actual
     */
    public static function getCurrentShift($now): ?Shift
    {
        $shift = Shift::query()
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

        return $shift;
    }
}
