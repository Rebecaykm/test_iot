<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRecord extends Model
{
    protected $fillable = [
        'work_center_id',
        'alert_id',
        'failure_id'
    ];

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

    /**
     *
     */
    public function failure(): BelongsTo
    {
        return $this->belongsTo(Failure::class, 'failure_id');
    }
}
