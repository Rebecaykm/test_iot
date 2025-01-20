<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    protected $fillable = [
        'part_number_id',
        'quantity',
        'sequence'
    ];

    /**
     *
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }
}
