<?php

namespace App;

use App\Traits\ApiResponser;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    use ApiResponser, UpdateGenericClass;

    protected $table = 'files';

    protected $fillable = [
        'user_id', 'file_url'
    ];
}
