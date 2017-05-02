<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone', 'email', 'firstname', 'lastname', 'dob'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public function photos(){
        return $this->belongsToMany(Photo::class)
            ->withTimestamps()
            ->withPivot('admin');
    }

    public function groups() {
        return $this->belongsToMany(Group::class)
            ->withTimestamps()
            ->withPivot('admin', 'status');
    }

    public function events() {
        return $this->belongsToMany(Event::class)
            ->withTimestamps()
            ->withPivot('status', 'admin');
    }

    public function friends() {
        return $this->belongsToMany(User::class, 'user_users', 'user_id', 'friend_id')
            ->withPivot('active', 'own')
            ->withTimestamps();
    }

    public function profile_pic() {
        return $this->belongsTo(Photo::class, 'profile_pic_id');
    }
    
    /*
    ** Define Followers, Many-To-Many self relation
    ** followers -> set the relationship in order to get followers of user
    **              Used to attach followers to an user
    ** followed -> set the relationship in order to get elements that user is following
    **             Used to attach followers to the current user
    */
    public function followers() {
        return $this->belongsToMany(User::class, 'followers', 'follow_id', 'user_id')
            ->withTimestamps();
    }

    public function followed() {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follow_id')
            ->withTimestamps();
    }

    public function getFriends() {
        return $this->friends()
            ->where('active', true);
    }

    public function routeNotificationForSlack() {
        return env('SLACK_WEBHOOK');
    }

    /**
     * Find the user identified by the given $identifier.
     *
     * @param $identifier nickname
     * @return mixed
     */
    public function findForPassport($nickname) {
        return $this->where('nickname', $nickname)
            ->first();
    }

    /*
    **
    ** Messenger section
    **
    */

    public function channels() {
        return $this->belongsToMany(Channel::class);
    }


    /*
    ** All messages from all channels
    */
    public function messages() {
        return $this->hasMany(Message::class);
    }

    /*
    ** Inventory
    */
    public function categories() {
        return $this->belongsToMany(Category::class)
            ->withPivot('count')
            ->withTimestamps();
    }
}
