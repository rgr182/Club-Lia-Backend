<?php

namespace App;
use App\Traits\UpdateGenericClass;

use Illuminate\Database\Eloquent\Model;

class teacherValidations extends Model
{
    use UpdateGenericClass;

    protected $table = 'teacher_validations';

    protected $fillable = [
        'id',
        'status',
        'level_school',
        'membership',
        'document_type',
        'school_name',
        'intereses',
        'uuid'
    ];

    public $timestamps = true;
}
