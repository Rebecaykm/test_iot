<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'production_rate',
        'efficiency',
        'item_class_id',
        'work_center_id',
        'is_obsolete'
    ];

    /**
     *
     */
    public function itemClass(): BelongsTo
    {
        return $this->belongsTo(ItemClass::class);
    }

    /**
     *
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     *
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'part_number_project', 'part_number_id', 'project_id');
    }

    /**
     *
     */
    public function histories(): HasMany
    {
        return $this->hasMany(History::class, 'part_number_id');
    }

    /**
     *
     */
    public function scrapRecords(): HasMany
    {
        return $this->hasMany(ScrapRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class, 'part_number_id');
    }

    /**
     *
     */
    public function visualAids(): HasMany
    {
        return $this->hasMany(VisualAid::class, 'part_number_id');
    }

    /**
     *
     */
    public function activeImage(): HasOne
    {
        return $this->hasOne(VisualAid::class)->where('is_active', true);
    }
}
