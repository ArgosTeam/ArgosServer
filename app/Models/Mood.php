<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mood extends Model
{
    protected $fillable = ['name'];
    
    public function photos() {
        return $this->hasMany(Photo::class);
    } 
}
