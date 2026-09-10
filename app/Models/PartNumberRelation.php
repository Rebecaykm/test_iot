<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PartNumberRelation extends Pivot
{
    protected $table = 'part_number_relations';

    public $incrementing = true;

    protected $fillable = [
        'parent_part_number_id',
        'child_part_number_id',
        'sequence_order',
        'effective_date',
        'discontinue_date',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'discontinue_date' => 'date',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}
