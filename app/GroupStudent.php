<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class GroupStudent extends Model
{
    //
    use UpdateGenericClass;

    protected $table = 'group_user_enrollments';
    protected $fillable = ['user_id','school_id','group_id','group_id_community', 'group_id_academy'];
}
