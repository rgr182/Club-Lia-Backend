<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use UpdateGenericClass;

    protected $table = 'contacts';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $fillable = [
        'user_id', 'phone_number', 'country', 'state', 'city'
    ];


}
