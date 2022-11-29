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

class UserLIA extends Authenticatable
{

    use UpdateGenericClass;
    protected $connection= 'sqlsrv';

    protected $table = 'dbo.AppUsers';
    protected $primaryKey = 'AppUserId';
    protected $fillable = [
        'AppUserId','AppUser', 'Names', 'LastNames','Password', 'RoleId', 'IsActive','Email', 'SchoolId', 'SchoolGroupKey', 'MemberSince', 'Grade', 'CreatorId','EditorId', 'Avatar'
    ];

    public $timestamps = false;
//    public function setPasswordAttribute($value)
//    {
//
//            $this->attributes['password'] = bcrypt($value);
//            $this->attributes['password'] = str_replace("$2y$", "$2a$", $this->attributes['password']);
//    }
}
