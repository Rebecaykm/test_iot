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
        'work_center_id',
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
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }
}
