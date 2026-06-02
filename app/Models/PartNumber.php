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
        'standard_pack_id',
        'standard_pack_quantity',
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
    public function standardPack(): BelongsTo
    {
        return $this->belongsTo(StandardPack::class, 'standard_pack_id');
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
}
