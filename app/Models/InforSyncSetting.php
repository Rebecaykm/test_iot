<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InforSyncSetting extends Model
{
    public const ENVIRONMENT_LIVE = 'live';
    public const ENVIRONMENT_PROTO = 'proto';

    protected $fillable = [
        'environment',
        'enabled',
        'work_center_ids',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'work_center_ids' => 'array',
    ];

    /**
     * Obtiene la configuración de un ambiente (live | proto)
     */
    public static function forEnvironment(string $environment): ?self
    {
        return static::where('environment', $environment)->first();
    }

    /**
     * Números de las estaciones (work centers) seleccionadas para este ambiente
     */
    public function workCenterNumbers(): array
    {
        return WorkCenter::whereIn('id', $this->work_center_ids ?? [])
            ->pluck('number')
            ->toArray();
    }
}
