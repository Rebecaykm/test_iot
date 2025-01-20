<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineStoppage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type_line_stoppage_id'
    ];

    /**
     *
     */
    public function typeLineStoppage(): BelongsTo
    {
        return $this->belongsTo(TypeLineStoppage::class, 'type_line_stoppage_id');
    }

    /**
     *
     */
    public function lineStoppageRecord(): HasMany
    {
        return $this->hasMany(lineStoppageRecord::class, 'line_stoppage_id');
    }
}
