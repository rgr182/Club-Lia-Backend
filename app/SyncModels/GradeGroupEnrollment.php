<?php

namespace App\SyncModels;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class GradeGroupEnrollment extends Model
{
    use UpdateGenericClass;
    protected $connection= 'sqlsrv';

    protected $table = 'dbo.GroupsStudents';
    protected $fillable = ['GroupStudentsId','StudentId','GroupId',];

    public $timestamps = false;
}
