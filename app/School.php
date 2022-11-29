<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;


class School extends Model
{
    use UpdateGenericClass;

    protected $primaryKey = 'id';

    protected $fillable = ['id','name','description','type','is_active','current_user','has_kinder','has_h2d','has_clplus'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

}
