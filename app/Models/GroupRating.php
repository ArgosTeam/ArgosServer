<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRating extends Model
{
    protected $table = 'group_rating';

    public function rating_type() {
        return $this->belongsTo(RatingType::class, 'rating_type_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function event() {
        return $this->belongsTo(Group::class);
    }
}