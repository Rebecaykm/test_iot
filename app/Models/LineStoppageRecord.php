<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineStoppageRecord extends Model
{
    protected $fillable = [
        'line_stoppage_id',
        'work_center_id',
        'start_time',
        'end_time',
        'minutes_stoppage'
    ];

    /**
     *
     */
    public function lineStoppage(): BelongsTo
    {
        return $this->belongsTo(LineStoppage::class, 'line_stoppage_id');
    }

    /**
     *
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }
}
