<?php

namespace App;

use App\Traits\UpdateGenericClass;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;

class LicenseKey extends Model
{
    use Uuid, UpdateGenericClass;

    protected $table = 'licenses_key';

    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    protected $fillable = [
        'user_id', 'license_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
