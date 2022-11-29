<?php

namespace App;

use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use UpdateGenericClass;

    protected $fillable = ['user_id','period_id','school_id', 'license_id', 'license_key_id', 'role_id', 'grade_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period()
    {
        return $this->belongsTo(Periodo::class, 'period_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function license()
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function key()
    {
        return $this->belongsTo(LicenseKey::class, 'license_key_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}
