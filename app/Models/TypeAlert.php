<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAlert extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     *
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'type_alert_id');
    }
}
