<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ["lat", "lng"];


    public function event() {
        return $this->hasOne(Event::class);
    }

    public function photo() {
        return $this->hasOne(Photo::class);
    }

    public function group() {
        return $this->hasOne(Group::class);
    }

    
}
