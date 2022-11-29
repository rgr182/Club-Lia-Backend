<?php

namespace App;

use App\Traits\ModelUserTrait;
use App\Traits\UpdateGenericClass;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, ModelUserTrait, UpdateGenericClass;

    protected $guarded = [];

    protected $fillable = [
        'id', 'AppUserId','uuid', 'username','role_id','level_id', 'tutor_id', 'school_id', 'company_id', 'name','second_name', 'last_name', 'second_last_name', 'email', 'grade', 'avatar','password', 'last_login'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'role_id' => 'required',
            'school_id' => 'required',
            'username' => 'required',
            'last_name' => 'required',
            'grade' => 'required',
            'password' => 'required',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime', 'member_since' =>'datetime', 'last_login' => 'datetime'
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

//    public function setPasswordAttribute($value)
//    {
//        $this->attributes['password'] = bcrypt($value);
//        $this->attributes['password'] = str_replace("$2y$", "$2a$", $this->attributes['password']);
//    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
    public function company()
    {
        return $this->belongsTo(School::class, 'company_id');
    }
}
