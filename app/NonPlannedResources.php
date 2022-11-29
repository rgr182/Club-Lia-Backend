<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class NonPlannedResources extends Model
{
    use UpdateGenericClass;

    protected $table = 'nonplanned_resources';
    protected $fillable = [
        'id',
        'id_class',
        'id_calendar',
        'id_resource'
    ];

    public $timestamps = false ;


}