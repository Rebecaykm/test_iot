<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scrap extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type_scrap_id'
    ];

    /**
     *
     */
    public function typeScrap(): BelongsTo
    {
        return $this->belongsTo(TypeScrap::class, 'type_scrap_id');
    }

    /**
     *
     */
    public function scrapRecords(): HasMany
    {
        return $this->hasMany(ScrapRecord::class, 'scrap_id');
    }

    /**
     * Get the default scraps associated with the scrap
     */
    public function defaultScraps(): HasMany
    {
        return $this->hasMany(PartNumberDefaultScrap::class, 'scrap_id');
    }
}
