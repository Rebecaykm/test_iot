<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ECL extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.ECL';

    protected $fillable = [
        'LORD',
        'LLINE',
        'LPROD',
        'LQORD',
        'LQALL',
        'LQSHP',
        'LUM',
        'LRDTE',
        'LSDTE',
        'CLIDNO',
        'CLCARD'
    ];
}
