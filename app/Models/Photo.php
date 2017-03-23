<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    protected $fillable = ['name', 'description', 'path', 'origin_user_id', 'location_id', 'md5', 'public', 'mode'];

    public function groups(){
        return $this->belongsToMany(Group::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot('admin');
    }

    public function location() {
        return $this->belongsTo(Location::class);
    }
       
    
    public function hashtags() {
        return $this->belongsToMany(Hashtag::class)
            ->withTimestamps();
    }

    public function comments() {
        return $this->belongsToMany(Comment::class)
            ->withTimestamps();
    }

    public function events() {
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }

    public function event_profile_pic() {
        return $this->hasOne(Event::class, 'profile_pic_id');
    }

    public function user_profile_pic() {
        return $this->hasOne(User::class, 'profile_pic_id');
    }

    public function group_profile_pic() {
        return $this->hasOne(Group::class, 'profile_pic_id');
    }

    public function ratings() {
        return $this->belongsTo(PhotoRating::class)
            ->withTimestamps();
    }
}
