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
        return $this->belongsToMany(User::class);
    }
}
