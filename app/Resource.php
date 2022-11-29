<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use ApiResponser;

    protected $fillable = [
        'custom_subject_id',
        'path',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y', 'update_at' => 'datetime:d-m-Y',
    ];
}
