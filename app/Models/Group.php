<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    protected $fillable = [
        'name', 'public', 'description', 'address'
    ];

    
    public function photos() {
        return $this->belongsToMany(Photo::class)
            ->withTimestamps();
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

    public function profile_pic() {
        return $this->belongsTo(Photo::class, 'profile_pic_id');
    }

    public function comments() {
        return $this->belongsToMany(Comment::class)
            ->withTimestamps();
    }
}
