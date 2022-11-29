<?php

namespace App;

use App\Traits\ApiResponser;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class ClassVC extends Model
{
    use ApiResponser, UpdateGenericClass;
    protected $table = 'classes';

    protected $fillable = [
        'teacher_id', 'meeting_id', 'group_id'
    ];
}
