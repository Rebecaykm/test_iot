<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_center_id',
        'container_code',
        'visual_aid_code',
        'final_label_code',
        'part_number',
        'validation_status',
        'validation_details',
    ];

    protected $casts = [
        'validation_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con WorkCenter
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Scope para filtrar por estado de validación
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('validation_status', $status);
    }

    /**
     * Scope para filtrar por centro de trabajo
     */
    public function scopeByWorkCenter($query, $workCenterId)
    {
        return $query->where('work_center_id', $workCenterId);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
