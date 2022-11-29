<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class DigitalResourcesCategories extends Model
{
    use UpdateGenericClass;
    
    protected $table = 'digital_resources_categories';
    protected $fillable = [
        'id',
        'name',
    ];
    
    public $timestamps = false ;
}
