<?php

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;

class FriendFunctions
{
    public static function add($user, $friend, $own = false, $active = false) {
        if ($user->friends->contains($friend->id)) {
            return response('Friendship already exists', 404);
        }
        $user->friends()->attach($friend->id, [
            'own' => $own,
            'active' =>$active
        ]);
        return response('success', 200);
    }

    public static function accept($user, $friend) {
        $user->friends()->updateExistingPivot($friend->id, [
            'active' => true
        ]);
        return response('success', 200);
    }

    public static function refuse($user, $friend) {
        $user->friends()->detach($friend->id);
        return response('success', 200);
    }

}