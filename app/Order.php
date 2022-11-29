<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use ApiResponser;

    protected $fillable = [
        'id', 'payment_id', 'merchant_order_id', 'preference_id', 'user_id', 'name','last_name', 'email', 'phone_number', 'child_id', 'id_licenses_type', 'id_course', 'license_type', 'unit_price', 'quantity', 'payment_type','status', 'expiry_date',
    ];
}
