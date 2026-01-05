<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attribute extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    /**
     * Get the parent model (PartNumber, WorkCenter, etc.)
     */
    public function attributable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessor for the value - VERSIÓN MEJORADA
     */
    public function getFormattedValueAttribute()
    {
        return match ($this->type) {
            'integer' => (int) $this->attributes['value'],
            'float' => (float) $this->attributes['value'],
            'boolean' => $this->attributes['value'] === '1' || $this->attributes['value'] === 'true',
            'array' => json_decode($this->attributes['value'], true) ?? [],
            default => $this->attributes['value'],
        };
    }

    /**
     * Convert value for storage
     */
    public function setValueAttribute($value)
    {
        // Detectamos el tipo primero
        $this->attributes['type'] = $this->detectDataType($value);

        // Convertimos el valor según el tipo
        $this->attributes['value'] = match ($this->attributes['type']) {
            'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Detect data type from value
     */
    protected function detectDataType($value): string
    {
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_bool($value)) return 'boolean';
        if (is_array($value)) return 'array';
        return 'string';
    }
}
