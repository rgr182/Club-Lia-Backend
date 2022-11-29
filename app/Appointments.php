<?php

namespace App;
use App\Traits\UpdateGenericClass;

use Illuminate\Database\Eloquent\Model;

class Appointments extends Model
{
    use UpdateGenericClass;

    protected $table = 'table_of_appointments';

    protected $fillable = [
        'id',
        'name',
        'last_name',
        'email',
        'phone_number',
        'city',
        'grades',
        'education_program',
        'additional_information',
        'requires_sep',
        'start_date',
        'date',
        'hour',
        'status'
    ];    

    public $timestamps = true;

    
}