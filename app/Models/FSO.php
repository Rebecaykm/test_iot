<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FSO extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.FSO';

    protected $fillable = [
        'SPROD',
        'SQREQ',
        'SRDTE',
        'SOCNO'
    ];
}
