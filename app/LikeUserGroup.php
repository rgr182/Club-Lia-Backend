<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LikeUserGroup extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_like';
    protected $fillable = [
        'type_id',
        'item_id',
        'user_id',
        'feed_table',
        'feed_table',
        'time_stamp'
    ];
    protected $primaryKey = 'like_id';
    public $timestamps = false;

}
