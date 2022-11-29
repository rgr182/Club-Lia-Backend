<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Tutor extends Model
{
    use HasApiTokens, Notifiable, UpdateGenericClass;

    protected $fillable = [
       'name','password',
    ];

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6|max:255',
    ];
}
