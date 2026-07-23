<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FSOProto extends Model
{
    protected $connection = 'infor-proto';
    protected $table = 'LX834F02.FSO';

    protected $fillable = [
        'SPROD',
        'SQREQ',
        'SRDTE',
        'SOCNO'
    ];
}
