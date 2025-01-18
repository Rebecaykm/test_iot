<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeLineStoppage extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     *
     */
    public function lineStoppages(): HasMany
    {
        return $this->hasMany(LineStoppage::class, 'type_line_stoppage_id');
    }
}
