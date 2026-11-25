<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Failure extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];
}
