<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IIM extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.IIM';

    protected $fillable = [
        'IID',
        'IPROD',
        'IDESC',
        'IREF04',
        'IMPLC',
        'IMSPKT'
    ];
}
