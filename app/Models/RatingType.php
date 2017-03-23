<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingType extends Model
{
    protected $table = 'rating_types';
    
    public function groups() {
        return $this->belongsToMany(Group::class,
                                    'group_rating',
                                    'group_id',
                                    'rating_type_id')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function photos() {
        return $this->belongsToMany(Photo::class,
                                    'photo_rating',
                                    'photo_id',
                                    'rating_type_id')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function events() {
        return $this->belongsToMany(Event::class,
                                    'event_rating',
                                    'event_id',
                                    'rating_type_id')
            ->withPivot('rate')
            ->withTimestamps();
    }
}