<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use ApiResponser;

    protected $fillable = [
        'club_name',
        'base_color'
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y', 'update_at' => 'datetime:d-m-Y',
    ];

    public function subjects()
    {
        return $this->hasMany('App\Subject');
    }
}
