<?php
/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 29/11/2016
 * Time: 7:18 AM
 */

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Friend;

class FriendFunctions
{
    public static function add($user, $friendId, $own = false, $active = false) {
        $friend = Friend::where("friend_id", $friendId)
                ->where("user_id", $user->id)
                ->first();
        if ($friend) {
            return ["status" => "refused",
                    "reason" => "friendship already exists",
                    "http" => 404];
        }
        $friend = new Friend();
        $friend->user_id = $user->id;
        $friend->friend_id = $friendId;
        $friend->active = $active;
        $friend->own = $own;
        if ($friend->save()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public static function accept($user, $friendId) {
        $friend = Friend::where("friend_id", $user->id)
                ->where("user_id", $friendId)
                ->first();
        $friend->active = true;
        if ($friend->save()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public static function refuse($user, $friendId) {
        $friend = Friend::where("friend_id", $user->id)
                ->where("user_id", $friendId)
                ->first();
        if (!is_object($friend)) {
            return response('Friendship does not exist', 404);
        }
        if ($friend->active) {
            return response('Not possible', 404);
        }
        if ($friend->delete()) {
            return response('Success', 200);
        } else {
            return response('Error while deleting', 404);
        }
    }

}