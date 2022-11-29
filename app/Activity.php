<?php

namespace App;

use App\Traits\ApiResponser;
use App\Traits\UpdateGenericClass;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use ApiResponser, UpdateGenericClass;

    protected $table = 'activity';

    protected $fillable = [
        'teacher_id', 'group_id', 'name', 'theme', 'platform', 'instructions', 'file_path', 'url_path', 'resources', 'finish_date', 'public_day', 'is_active', 'subject_id'
    ];
}
