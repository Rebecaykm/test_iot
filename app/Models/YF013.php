<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YF013 extends Model
{
    protected $connection = 'infor-proto';
    protected $table = 'LX834FU01.YF013';

    protected $fillable = [
        'YFWRKC',
        'YFWRKN',
        'YFRDTE',
        'YFSHFT',
        'YFPPNO',
        'YFPROD',
        'YFSTIM',
        'YFETIM',
        'YFSDT',
        'YFEDT',
        'YFQPLA',
        'YFQPRO',
        'YFQSCR',
        'YFSCRE',
        'YFCRDT',
        'YFCRTM',
        'YFCRUS',
        'YFCRWS',
        'YFFIL1',
        'YFFIL2',
    ];
}
