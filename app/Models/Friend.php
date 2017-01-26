<?php

namespace App\Models;

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

    public function users() {
        return $this->hasMany(User::class);
    }
}
