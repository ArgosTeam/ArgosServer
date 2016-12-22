<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    //
    use SoftDeletes;

    protected $fillable = ["name", "description", "path", "user_id", "lat", "lng", "md5"];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function groups(){
        return $this->belongsToMany(Group::class);
    }

    public function hashtags(){
        return $this->belongsToMany(Hashtag::class);
    }
}
