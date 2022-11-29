<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhpFox_user_activity extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_user_activity';

    //protected $primaryKey = 'user_id';
    public $incrementing   = false;

    protected $fillable  = [
        'user_id',
    ];
    public $timestamps = false;
}
