<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    //
    protected $fillable = ["name", "user_id", "public", "lat", "lng"];


    public function events(){
        return $this->hasMany(Event::class);
    }

}
