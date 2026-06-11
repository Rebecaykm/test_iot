<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
     * Devuelve un mapa [part_number_id => divisor] para convertir piezas a golpes,
     * calculado SOLO sobre los part numbers indicados (los que están en juego en
     * el turno/producción), no sobre todo el work center.
     *
     * Esto es clave: un troquel (atributo 'mid') puede estar registrado en part
     * numbers que ya no se corren (revisiones viejas, ej. BDWK34811A vs BDWK34811B).
     * Si esos contaran, el divisor se inflaría y los golpes saldrían más bajos.
     * Por eso el divisor solo suma el pieces_per_shot de los parts presentes aquí.
     *
     *     golpes = piezas_del_part / divisor_del_part
     *
     * Ej.: troquel BDWK34831/841 con BDWK34831 y BDWK34841 (ambos en el turno),
     * cada uno produjo 1423 piezas (pps=1) => divisor 2 => 1423/2 + 1423/2 = 1423.
     */
    public static function buildShotDivisorsForPartIds(array $partIds): array
    {
        $partIds = array_values(array_unique(array_filter($partIds)));

        if (empty($partIds)) {
            return [];
        }

        $parts = static::query()
            ->whereIn('id', $partIds)
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get();

        return static::buildShotDivisors($parts);
    }

    /**
     * Construye el mapa [part_number_id => divisor] a partir de una colección de
     * PartNumber (distintos) que ya tienen cargados sus customAttributes ('mid' y
     * 'pieces_per_shot'). Ver buildShotDivisorsForPartIds() para el detalle.
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
