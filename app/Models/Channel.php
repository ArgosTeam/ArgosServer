<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    public function group() {
        return $this->hasOne(Group::class);
    }

    public function event() {
        return $this->hasOne(Event::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withPivot('last_seen_message_id')
            ->withTimestamps();
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function photo() {
        return $this->hasOne(Photo::class);
    }
}
