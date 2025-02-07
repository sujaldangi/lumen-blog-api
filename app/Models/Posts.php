<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{
    //
    protected $fillable = [
        'post_title', 'post_description','post_content','post_type', 'featured_image','author_id', 'category','status',
    ];

   
}
