<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    //
    protected $fillable = ['user_id', 'post_id','post_comment'];

    public function post()
    {
        return $this->belongsTo(Posts::class,'post_id');
    }
}
