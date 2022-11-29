<?php

namespace App\SyncModels;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class GroupStudentEnrollment extends Model
{
    use UpdateGenericClass;
    protected $connection= 'sqlsrv';

    protected $table = 'dbo.Groups';
    protected $fillable = ['GroupId','Code','Name','TeacherId','SchoolId','Grade','CreateOn', 'IsActive'];

    public $timestamps = false;
}
