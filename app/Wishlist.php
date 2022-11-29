<?php

namespace App;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use UpdateGenericClass;

    protected $table = 'wishlist';

    protected $fillable = [
        'id',
        'course_id',
        'user_id',
    ];

    public $timestamps = true;
}
