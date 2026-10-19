<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InforSyncWorkCenter extends Model
{
    protected $fillable = [
        'infor_sync_setting_id',
        'work_center_id',
        'sync_from',
    ];

    protected $casts = [
        'sync_from' => 'date',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(InforSyncSetting::class, 'infor_sync_setting_id');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id');
    }
}
