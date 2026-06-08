<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class PartNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'production_rate',
        'efficiency',
        'item_class_id',
        'work_center_id',
        'is_obsolete',
        'production_order'
    ];

    /**
     * Scope para ordenar por orden de producción
     */
    public function scopeOrderByProduction($query)
    {
        return $query->orderBy('production_order', 'asc');
    }

    /**
     * Scope para obtener números de parte por work center ordenados
     */
    public function scopeByWorkCenterOrdered($query, $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId)
            ->orderBy('production_order', 'asc');
    }

    /**
     * Método para obtener el siguiente número de parte en el orden de producción
     */
    public function getNextInProductionOrder()
    {
        return static::where('work_center_id', $this->work_center_id)
            ->where('production_order', '>', $this->production_order)
            ->orderBy('production_order', 'asc')
            ->first();
    }

    /**
     * Método para obtener el número de parte anterior en el orden de producción
     */
    public function getPreviousInProductionOrder()
    {
        return static::where('work_center_id', $this->work_center_id)
            ->where('production_order', '<', $this->production_order)
            ->orderBy('production_order', 'desc')
            ->first();
    }

    /**
     *
     */
    public function itemClass(): BelongsTo
    {
        return $this->belongsTo(ItemClass::class);
    }

    /**
     *
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     *
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'part_number_project', 'part_number_id', 'project_id');
    }

    /**
     *
     */
    public function histories(): HasMany
    {
        return $this->hasMany(History::class, 'part_number_id');
    }

    /**
     *
     */
    public function scrapRecords(): HasMany
    {
        return $this->hasMany(ScrapRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function visualAids(): HasMany
    {
        return $this->hasMany(VisualAid::class, 'part_number_id');
    }

    /**
     *
     */
    public function activeImage(): HasOne
    {
        return $this->hasOne(VisualAid::class)->where('is_active', true);
    }

    /**
     * Get all custom attributes for this part number
     */
    public function customAttributes(): MorphMany
    {
        return $this->morphMany(Attribute::class, 'attributable');
    }

    /**
     * Get a specific custom attribute value
     */
    public function getCustomAttributeValue(string $key)
    {
        $attribute = $this->customAttributes()->where('key', $key)->first();
        return $attribute ? $attribute->value : null;
    }

    /**
     * Set a custom attribute value
     */
    public function setCustomAttributeValue(string $key, $value)
    {
        $this->customAttributes()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Recargar la relación después de modificar
        $this->load('customAttributes');
    }

    /**
     * Remove a custom attribute - VERSIÓN CORREGIDA
     */
    public function removeCustomAttribute(string $key): bool
    {
        // Usar la relación para eliminar
        $deleted = $this->customAttributes()
            ->where('key', $key)
            ->delete();

        // Forzar la recarga de la relación
        $this->load('customAttributes');

        return $deleted > 0;
    }

    /**
     * Check if custom attribute exists
     */
    public function hasCustomAttribute(string $key): bool
    {
        return $this->customAttributes()->where('key', $key)->exists();
    }

    /**
     * Get all custom attributes as key-value pairs - VERSIÓN CORREGIDA
     */
    public function getAllCustomAttributes(): array
    {
        // Usar la relación (query builder) en lugar de la colección cargada
        return $this->customAttributes()
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Devuelve un mapa [part_number_id => divisor] para convertir piezas a golpes.
     *
     * Un golpe del troquel (atributo 'mid') produce simultáneamente piezas de
     * TODOS los números de parte que comparten ese troquel, por eso el divisor
     * es la SUMA de pieces_per_shot de esos parts:
     *
     *     golpes = piezas_del_part / divisor_del_part
     *
     * Así, sumando golpes por part se obtiene el total real del troquel sin
     * duplicar (ej. troquel BDWK34831/841 con parts BDWK34831 y BDWK34841,
     * cada uno pieces_per_shot=2 => divisor 4: 100/4 + 100/4 = 50 golpes).
     *
     * Los parts sin troquel forman su propio grupo (divisor = su pieces_per_shot).
     */
    public static function getShotDivisorsByWorkCenter(string $workCenter): array
    {
        $workCenterId = Cache::remember(
            "work_center_id:{$workCenter}",
            now()->addHours(6),
            fn () => WorkCenter::where('name', $workCenter)->value('id')
        );

        if (! $workCenterId) {
            return [];
        }

        $parts = static::query()
            ->where('work_center_id', $workCenterId)
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get();

        return static::buildShotDivisors($parts);
    }

    /**
     * Construye el mapa [part_number_id => divisor] a partir de una colección de
     * PartNumber que ya tienen cargados sus customAttributes ('mid' y
     * 'pieces_per_shot'). Ver getShotDivisorsByWorkCenter() para el detalle.
     */
    public static function buildShotDivisors($parts): array
    {
        $piecesPerShot = [];
        $dieKey        = [];

        foreach ($parts as $part) {
            $attrs = $part->customAttributes->keyBy('key');

            $pps = max(1, (int) ($attrs['pieces_per_shot']->value ?? 1));
            $mid = trim((string) ($attrs['mid']->value ?? ''));

            $piecesPerShot[$part->id] = $pps;
            // Sin troquel => grupo propio para no mezclarlo con otros parts.
            $dieKey[$part->id] = $mid !== '' ? $mid : "part:{$part->id}";
        }

        // Suma de pieces_per_shot por troquel.
        $dieTotals = [];
        foreach ($piecesPerShot as $partId => $pps) {
            $key             = $dieKey[$partId];
            $dieTotals[$key] = ($dieTotals[$key] ?? 0) + $pps;
        }

        // Cada part hereda como divisor el total de su troquel.
        $divisors = [];
        foreach ($dieKey as $partId => $key) {
            $divisors[$partId] = max(1, $dieTotals[$key]);
        }

        return $divisors;
    }
}
