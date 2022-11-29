<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use UpdateGenericClass;

    protected $table = 'topics';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'this_order',
    ];
}