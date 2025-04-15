<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisualAid extends Model
{
    protected $fillable = [
        'part_number_id',
        'path',
        'alt_text',
        'is_active'
    ];

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    protected static function booted()
    {
        static::updating(function ($image) {
            if ($image->isDirty('is_active') && $image->is_active) {
                self::where('part_number_id', $image->part_number_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_active' => false]);
            } else {

                $activeCount = self::where('part_number_id', $image->part_number_id)
                    ->where('is_active', true)
                    ->count();

                if ($activeCount === 0) {
                    self::where('part_number_id', $image->part_number_id)
                        ->where('id', '!=', $image->id)
                        ->latest()
                        ->first()?->update(['is_active' => true]);
                }
            }
        });

        static::creating(function ($image) {
            if ($image->is_active) {
                self::where('part_number_id', $image->part_number_id)
                    ->update(['is_active' => false]);
            }
        });
    }
}
