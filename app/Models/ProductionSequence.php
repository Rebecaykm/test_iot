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

    /**
     * Store a new production sequence
     *
     * @param int $productionRecordId
     * @param int $sequenceNumber
     * @param int $quantity
     * @return ProductionSequence
     */
    public static function store(int $productionRecordId, int $sequenceNumber, int $quantity): ProductionSequence
    {
        return ProductionSequence::create([
            'production_record_id' => $productionRecordId,
            'sequence_number' => $sequenceNumber,
            'quantity' => $quantity,
        ]);
    }
}
