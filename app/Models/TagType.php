<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     *
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'tag_type_id');
    }
}
