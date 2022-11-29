<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdateGenericClass;

class Badge extends Model
{
    use UpdateGenericClass;

    protected $table = 'badges';

    protected $fillable = [
        'id',
        'name',
        'description',
        'badge',
        'score'
    ];

    public $timestamps = false ;
}
