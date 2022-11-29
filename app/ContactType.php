<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    use UpdateGenericClass;

    protected $table = 'contact_type';
    protected $guarded = [];

    protected $fillable = [
        'description',
    ];

}
