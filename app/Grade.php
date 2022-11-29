<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use UpdateGenericClass;

    protected $fillable = ['grade_number','grade_name','school_level'];
}
