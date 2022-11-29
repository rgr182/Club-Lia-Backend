<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;


class SchoolLIA extends Model
{
    use UpdateGenericClass;
    protected $connection= 'sqlsrv';

    protected $table = 'dbo.Schools';
    protected $fillable = ['SchoolId','School','Description','IsActive','CurrentUsers','HasKinder','HasH2D','HasCLPlus','CreatorId','EditorId', ];

    protected $primaryKey = 'SchoolId';
    public $timestamps = false;
}
