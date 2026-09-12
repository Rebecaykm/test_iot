<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota libre sobre un MDI dentro de un turno/fecha específico de la línea de
 * tiempo de prensas (por qué se alargó, falla de troquel, falta de material, etc.).
 * Se relaciona por clave natural (work_center + mdi + shift + date) porque el
 * MDI no es una entidad propia en el esquema: es un atributo de texto sobre
 * part_numbers, compartido por varios números de parte.
 */
class PressTimelineNote extends Model
{
    protected $fillable = [
        'work_center',
        'mdi',
        'shift_id',
        'date',
        'body',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Notas del turno/fecha exacto, agrupadas por MDI (clave = etiqueta del MDI).
     */
    public static function forContext(string $workCenter, int $shiftId, \Carbon\Carbon $date): Collection
    {
        return static::query()
            ->where('work_center', $workCenter)
            ->where('shift_id', $shiftId)
            ->whereDate('date', $date)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('mdi');
    }
}
