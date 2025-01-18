<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    /**
     *
     */
    public function lines(): HasMany
    {
        return $this->hasMany(Line::class, 'area_id');
    }
}
