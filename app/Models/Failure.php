<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Failure extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     *
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'failure_id');
    }
}
