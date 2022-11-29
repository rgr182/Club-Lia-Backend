<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ApiResponser;
use App\Traits\UpdateGenericClass;

class GoogleToken extends Model
{
    //
    use ApiResponser, UpdateGenericClass;

    protected $table = 'google_token';

    protected $fillable = [
        'user_id', 'token', 'is_active', 'refresh_token', 'gmail'
    ];
}
