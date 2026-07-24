<?php

namespace App\Models;

use App\Models\Concerns\HasYf013Snapshot;
use Illuminate\Database\Eloquent\Model;

class YF013Live extends Model
{
    use HasYf013Snapshot;

    protected $connection = 'infor-live';
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
