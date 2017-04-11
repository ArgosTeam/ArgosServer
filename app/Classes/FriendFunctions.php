<?php

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\FriendRequest;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestRejected;

class FriendFunctions
{
    public static function add($user, $friend, $own = false, $active = false) {
        if ($user->friends->contains($friend->id)) {
            return response('Friendship already exists', 403);
        }
        $user->friends()->attach($friend->id, [
            'own' => $own,
            'active' =>$active
        ]);
        if ($own) {
            $user->notify(new FriendRequest($user, $friend, 'slack'));
        } else {
            $user->notify(new FriendRequest($user, $friend, 'database'));
        }
        return response(['status' => 'success'], 200);
    }

    public static function accept($user, $friend, $own = false) {
        $user->friends()->updateExistingPivot($friend->id, [
            'active' => true
        ]);
        if ($own) {
            $user->notify(new FriendRequestAccepted($user, $friend, 'database'));
        } else {
            $user->notify(new FriendRequestAccepted($user, $friend, 'slack'));
        }
        return response(['status' => 'success'], 200);
    }

    public static function refuse($user, $friend, $own = false) {
        $pivot = $user->friends()->where('users.id', $friend->id)->first();
        if (is_object($pivot) && !$pivot->pivot->active) {
            $user->friends()->detach($friend->id);

            if ($own) {
                $user->notify(new FriendRequestRejected($user, $friend, 'database'));
            } else {
                $user->notify(new FriendRequestRejected($user, $friend, 'slack'));    
            }
        
            return response(['status' => 'success'], 200);
        }
        
        return response(['status' => 'Access denied'], 403);
    }

    public static function cancel($user, $friend) {
        $user->friends()->detach($friend->id);
        return response(['status' => 'success'], 200);
    }

}
