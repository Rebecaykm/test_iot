<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapRecord extends Model
{
    protected $fillable = [
        'part_number_id',
        'scrap_id',
        'quantity'
    ];

    /**
     *
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    /**
     *
     */
    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class, 'scrap_id');
    }
}
