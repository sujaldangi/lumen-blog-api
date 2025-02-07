<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = ['user_id', 'post_id','rating'];

    public function post()
    {
        return $this->belongsTo(Posts::class,'post_id');
    }
}
