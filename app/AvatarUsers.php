<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdateGenericClass;

class AvatarUsers extends Model
{
    use UpdateGenericClass;

    protected $table = 'avatar_users';
    protected $fillable = [
        'id',
        'user_id',
        'avatar_id',
        'custom_name',
        'avatar_path'
    ];

    public $timestamps = false ;
}
