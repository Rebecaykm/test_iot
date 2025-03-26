<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IIC extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.IIC';

    protected $fillable = [
        'IID',
        'ICLAS',
        'ICDES'
    ];
}
