<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use ApiResponser;

    protected $fillable = [
        'name',
        'club_id',
        'base_color',
    ];
}
