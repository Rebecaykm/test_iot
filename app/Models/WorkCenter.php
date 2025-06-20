<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'ip',
        'tag_id',
        'line_id',
        'position_x',
        'position_y',
        'width',
        'height'
    ];

    /**
     *
     */
    public function tags()
    {
        return $this->hasMany(Tag::class, 'work_center_id');
    }

    /**
     *
     */
    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    /**
     *
     */
    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class, 'work_center_id');
    }

    /**
     *
     */
    public function alertRecords(): HasMany
    {
        return $this->hasMany(AlertRecord::class, 'work_center_id');
    }

    /**
     *
     */
    public function lineStoppageRecord(): HasMany
    {
        return $this->hasMany(LineStoppageRecord::class, 'work_center_id');
    }

    /**
     *
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Relación con las validaciones de material del centro de trabajo
     *
     * @return HasMany
     */
    public function materialValidations(): HasMany
    {
        return $this->hasMany(MaterialValidation::class, 'work_center_id');
    }
}
