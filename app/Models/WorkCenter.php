<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'ip',
        'tag_id',
        'line_id',
        'position_x',
        'position_y',
        'width',
        'height'
    ];

    /**
     *
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    /**
     *
     */
    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    /**
     *
     */
    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class, 'work_center_id');
    }

    /**
     *
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'work_center_id');
    }

    /**
     *
     */
    public function lineStoppageRecord(): HasMany
    {
        return $this->hasMany(LineStoppageRecord::class, 'work_center_id');
    }
}
