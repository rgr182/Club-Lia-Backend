<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhpFoxPageText extends Model
{
    protected $connection= 'mysql2';
    protected $table ='phpfox_pages_text';

    protected $primaryKey = 'page_id';
    public $incrementing   = false;

    protected $fillable  = [
        'page_id',
        'text',
        'text_parsed'
    ];
    public $timestamps = false;
}
