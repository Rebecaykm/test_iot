<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type_alert_id',
        'work_center_id',
        'bit_device',
        'word_device',
        'bit_index',
        'stop_category',
    ];

    /**
     * Estación dueña del bit
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }

    /**
     *
     */
    public function typeAlert(): BelongsTo
    {
        return $this->belongsTo(TypeAlert::class, 'type_alert_id');
    }

    /**
     *
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'alert_id');
    }
}
