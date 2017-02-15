<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['name',
                           'description',
                           'public',
                           'start',
                           'expires',
                           'created_at',
                           'updated_at'];

    public function location() {
        return $this->belongsTo(Location::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot('admin', 'status');
    }

    public function hashtags() {
        return $this->belongsToMany(Hashtag::class)
            ->withTimestamps();
    }

    public function comments() {
        return $this->belongsToMany(Comment::class)
            ->withTimestamps();
    }

    public function profile_pic() {
        return $this->belongsTo(Photo::class, 'profile_pic_id');
    }
}
