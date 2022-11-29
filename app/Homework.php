<?php

namespace App;

use App\Traits\ApiResponser;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use ApiResponser, UpdateGenericClass;

    protected $table = 'homework';

    protected $fillable = [
        'student_id', 'activity_id', 'status', 'score', 'delivered_date', 'file_path', 'url_path', 'is_active', 'delivery_date', 'scored_date'
    ];
    //
} 
