<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCommunity extends Model
{
    protected $connection = 'mysql2';
    protected $table = "phpfox_user";

    protected $primaryKey = 'user_id';

    protected $fillable = ['user_id',  'profile_page_id','view_id','user_group_id','email', 'full_name', 'password', 'password_salt', 'gender', 'user_name', 'joined', 'last_login'];

    public $timestamps = false;

}
