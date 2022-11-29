<?php

namespace App;

use App\Traits\ModelLicenseTrait;
use App\Traits\UpdateGenericClass;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use Uuid, ModelLicenseTrait, UpdateGenericClass;

    protected $table = 'licenses';
    protected $guarded = [];

    protected $fillable = [
        'titular', 'email_admin','school_id', 'license_type_id', 'user_id', 'studens_limit',
    ];

    protected $casts = [
        'purchase_at' => 'datetime', 'expiration_date' => 'datetime'
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
    public function licenseType()
    {
        return $this->belongsTo(LicenseType::class, 'license_type_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function index()
    {
        $licenses = License::get()->toJson(JSON_PRETTY_PRINT);
        return response($licenses, 200);
    }

}
