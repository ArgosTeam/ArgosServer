<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoRating extends Model
{
    protected $table = 'photo_rating';

    public function rating_type() {
        return $this->belongsTo(RatingType::class, 'rating_type_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function photo() {
        return $this->belongsTo(Photo::class);
    }
}