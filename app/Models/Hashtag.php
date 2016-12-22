<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hashtag extends Model
{
    //
    use SoftDeletes;

    public function photos(){
        return $this->belongsToMany(Photo::class);
    }

}
