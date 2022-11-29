<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class Donors extends Model
{
    use ApiResponser;

    protected $fillable =  [
        'id',
        'institution',
        'business_name',
        'position',
        'name',
        'logo',
        'publish_donors',
        'publish_logo',
        'id_order',
        'id_rol',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime', 'updated_at' => 'datetime',
        'publish_donors' => 'boolean',
        'publish_logo' => 'boolean'
    ];

    public function show(){
        $donors = Donors::where('publish_logo', true)->get();
        return $donors;
    }
}
