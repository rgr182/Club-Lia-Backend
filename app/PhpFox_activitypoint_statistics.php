<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhpFox_activitypoint_statistics extends Model
{
    //
    protected $connection= 'mysql2';
    protected $table ='phpfox_activitypoint_statistics';

    //protected $primaryKey = 'user_id';
    public $incrementing   = false;

    protected $fillable  = [
        'user_id',
    ];
    public $timestamps = false;
}
