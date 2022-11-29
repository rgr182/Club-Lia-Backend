<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhpFox_user_count extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_user_count';

    //protected $primaryKey = 'user_id';
    public $incrementing   = false;

    protected $fillable  = [
        'user_id',
    ];
    public $timestamps = false;
}
