<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'type',
        'model',
        'prefix',
        'client_id'
    ];

    /**
     *
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     *
     */
    public function partNumbers()
    {
        return $this->belongsToMany(PartNumber::class, 'part_number_project', 'project_id', 'part_number_id');
    }
}
