<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hashtag extends Model
{
    protected $fillable = ['name', 'count'];
    
    public function photos() {
        return $this->belongsToMany(Photo::class)
            ->withTimestamps();
    }

    public function groups() {
        return $this->belongsToMany(Group::class)
            ->withTimestamps();
    }

    public function events() {
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }
}
