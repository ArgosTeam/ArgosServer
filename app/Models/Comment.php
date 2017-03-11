<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id',
                           'content',
                           'created_at',
                           'updated_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function events() {
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }

    public function groups() {
        return $this->belongsToMany(Group::class)
            ->withTimestamps();
    }

    public function photos() {
        return $this->belongsToMany(Photo::class)
            ->withTimestamps();
    }
}
