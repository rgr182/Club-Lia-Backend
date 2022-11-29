<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use UpdateGenericClass;

    protected $table = 'roles';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'this_order', 'role_number'
    ];
}
