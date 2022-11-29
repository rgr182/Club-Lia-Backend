<?php

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Model;

class Phpfox_pages_admin extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_pages_admin';

    //protected $primaryKey = 'user_id';
    public $incrementing   = false;

    protected $fillable  = [
        'page_id',
        'user_id'
    ];

    public $timestamps = false;
}
