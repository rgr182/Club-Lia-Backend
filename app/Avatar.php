<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Avatar extends Model
{
    use UpdateGenericClass;
    
    protected $table = 'avatar';
    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
        'path'
    ];
    
    public $timestamps = false ;
}