<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;


class Periodo extends Model
{
    use UpdateGenericClass;

    protected $primaryKey = 'id';
    protected $fillable = ['id','periodo','name','description','is_active','is_current',];
}
