<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRecord extends Model
{
    protected $fillable = [
        'part_number_id',
        'planned_quantity',
        'produced_quantity',
        'scrap_quantity',
        'planned_date',
        'production_start',
        'production_end',
        'shift_id',
        'status_id'
    ];

    /**
     *
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     *
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     *
     */
    public function partNumber() : BelongsTo {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }
}
