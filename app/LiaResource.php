<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;

class LiaResource extends Model
{
    use ApiResponser;

    protected $fillable = [
        'subject_id',
        'path',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y', 'update_at' => 'datetime:d-m-Y',
    ];
}
