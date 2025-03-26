<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemClass extends Model
{
    protected $fillable = [
        'abbreviation',
        'name'
    ];

    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class);
    }
}
