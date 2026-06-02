<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSequence extends Model
{
    protected $fillable = [
        'production_record_id',
        'sequence_number',
        'quantity',
        'is_processed',
        'payload'
    ];

    /**
     *
     */
    public function productionRecord()
    {
        return $this->belongsTo(ProductionRecord::class);
    }
}
