<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     *
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     *
     */
    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class, 'line_id');
    }
}
