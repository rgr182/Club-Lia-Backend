<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdateGenericClass;

class BadgeRelations extends Model
{
    use UpdateGenericClass;

    protected $table = 'badges_relations_';

    protected $fillable = [
        'badge_id',
        'task_id',
        'student_id',
        'teacher_id',
        'badges_data'
    ];

    public $timestamps = false ;
}
