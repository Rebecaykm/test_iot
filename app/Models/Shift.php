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
     *
     */
    public function productionRecord(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'shift_id');
    }
}
