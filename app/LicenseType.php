<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class LicenseType extends Model
{
    use UpdateGenericClass;

    protected $table = 'licenses_type';

    protected $fillable = [
        'id',
        'title',
        'description_license_type',
        'price',
        'category_id',
        'sold'
    ];
}
