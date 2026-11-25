<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRecord extends Model
{
    protected $fillable = [
        'work_center_id',
        'alert_id',
        'start_time',
        'end_time',
        'shift_id',
        'shift_date',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'shift_date' => 'date',
    ];

    /** Turno en que empezó la falla */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     *
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     *
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }
}
