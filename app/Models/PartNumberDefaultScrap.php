<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartNumberDefaultScrap extends Model
{
    protected $fillable = [
        'part_number_id',
        'scrap_id',
        'quantity',
        'description',
    ];

    /**
     * Get the part number associated with the default scrap.
     */
    public function partNumber()
    {
        return $this->belongsTo(PartNumber::class);
    }

    /**
     * Get the scrap associated with the default scrap.
     */
    public function scrap()
    {
        return $this->belongsTo(Scrap::class);
    }
}
