<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_users';
    protected $fillable = ['user_id', 'friend_id'];
}