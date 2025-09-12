<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Line extends Model
{
    protected $fillable = [
        'name',
        'color',
        'description',
        'area_id'
    ];

    /**
     * Relación con el área
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Relación con los centros de trabajo
     */
    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class, 'line_id');
    }

    /**
     * Relación con los usuarios (muchos a muchos)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
