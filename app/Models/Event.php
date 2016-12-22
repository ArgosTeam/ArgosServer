<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    protected $fillable = ["name", "user_id", "location_id", "public", "type", "expires"];

    public function location(){
        return $this->belongsTo(Location::class);
    }
}
