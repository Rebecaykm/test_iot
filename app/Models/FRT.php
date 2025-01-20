<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FRT extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.FRT';

    protected $fillable = [
        'RID',
        'RPROD',
        'RWRKC',
        'RDDDT'
    ];
}
