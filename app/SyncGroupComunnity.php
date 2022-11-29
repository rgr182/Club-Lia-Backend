<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SyncGroupComunnity extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_pages';
    protected $primaryKey = 'page_id';

    protected $fillable = [
        'page_id',
        'app_id',
        'view_id',
        'type_id',
        'category_id',
        'user_id',
        'title',
        'reg_method',
        'landing_page',
        'time_stamp',
        'image_path',
        'is_featured',
        'is_sponsor',
        'image_server_id',
        'total_like',
        'total_dislike',
        'total_comment',
        'privacy',
        'designer_style_id',
        'cover_photo_id',
        'cover_photo_position',
        'location_latitude',
        'location_longitude',
        'location_name',
        'use_timeline',
        'item_type'
    ];

    public $timestamps = false;
}
