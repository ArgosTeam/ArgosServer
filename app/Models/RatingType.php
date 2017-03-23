<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingType extends Model
{
    protected $table = 'rating_types';
    
    public function event_ratings() {
        return $this->hasMany(EventRating::class)
            ->withTimestamps();
    }

    public function photo_ratings() {
        return $this->hasMany(PhotoRating::class)
            ->withTimestamps();
    }

    public function group_ratings() {
        return $this->hasMany(GroupRating::class)
            ->withTimestamps();
    }
}