<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;

class InforSyncSetting extends Model
{
    public const ENVIRONMENT_LIVE = 'live';
    public const ENVIRONMENT_PROTO = 'proto';

    protected $fillable = [
        'environment',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Obtiene la configuración de un ambiente (live | proto)
     */
    public static function forEnvironment(string $environment): ?self
    {
        return static::where('environment', $environment)->first();
    }

    /**
     * Estaciones seleccionadas para este ambiente, con su fecha de inicio de envío
     */
    public function workCenters(): HasMany
    {
        return $this->hasMany(InforSyncWorkCenter::class, 'infor_sync_setting_id');
    }

    /**
     * Números de las estaciones cuya fecha de inicio de envío ya llegó
     */
    public function workCenterNumbers(): array
    {
        return WorkCenter::query()
            ->whereIn('id', $this->workCenters()->whereDate('sync_from', '<=', today())->select('work_center_id'))
            ->pluck('number')
            ->toArray();
    }

    /**
     * Limita una consulta de production_records (que ya une work_centers) a los
     * registros con planned_date >= la fecha de inicio de envío de su estación
     */
    public function applySyncFrom(Builder $query): Builder
    {
        return $query
            ->join('infor_sync_work_centers', function (JoinClause $join) {
                $join->on('infor_sync_work_centers.work_center_id', '=', 'work_centers.id')
                    ->where('infor_sync_work_centers.infor_sync_setting_id', $this->id);
            })
            ->whereColumn('production_records.planned_date', '>=', 'infor_sync_work_centers.sync_from');
    }
}
