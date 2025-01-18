<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $fillable = [
        'address',
        'long',
        'value',
        'description',
        'tag_type_id',
    ];

    /**
     *
     */
    public function tagType(): BelongsTo
    {
        return $this->belongsTo(TagType::class, 'tag_type_id');
    }

    /**
     *
     */
    public function workCenters(): HasMany
    {
        return $this->hasMany(WorkCenter::class, 'tag_id');
    }
}
