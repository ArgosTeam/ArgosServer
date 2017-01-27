<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ["lat", "lng"];


    public function events() {
        return $this->hasOne(Event::class);
    }

    public function photos() {
        return $this->hasOne(Photo::class);
    }
    
}
