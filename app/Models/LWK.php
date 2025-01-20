<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LWK extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834F01.LWK';

    protected $fillable = [
        'WID',
        'WWRKC',
        'WDESC',
        'WDEPT',
        'WFORE'
    ];
}
