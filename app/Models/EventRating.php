<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRating extends Model
{
    protected $table = 'event_rating';

    public function rating_type() {
        return $this->belongsTo(RatingType::class, 'rating_type_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function event() {
        return $this->belongsTo(Event::class);
    }
}