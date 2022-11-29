<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class DigitalResources extends Model
{
    use UpdateGenericClass;
    
    protected $table = 'digital_resources';
    protected $fillable = [
        'id',
        'bloque',
        'grade',
        'level',
        'name',
        'url_resource',
        'id_materia_base',
        'id_category',
        'description'
    ];
    
    public $timestamps = false ;
}