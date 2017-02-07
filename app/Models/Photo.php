<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    protected $fillable = ['name', 'description', 'path', 'origin_user_id', 'location_id', 'md5', 'public'];

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
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }
}
