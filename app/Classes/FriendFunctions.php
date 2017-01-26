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
    public static function add($user, $friendId, $active = false) {
        $friend = App\Models\Friend::where("friend_id", $friendId)
                ->where("user_id", $user->id)
                ->first();
        if ($friend) {
            return ["status" => "refused",
                    "reason" => "friendship already exists",
                    "http" => 404];
        }
        $friend = new App\Models\Friend();
        $friend->user_id = $user->id;
        $friend->friend_id = $friendId;
        $friend->active = $active;
        if ($friend->save()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public static function accept($user, $friendId) {
        $friend = App\Models\Friend::where("friend_id", $user->id)
                ->where("user_id", $friendId)
                ->first();
        $friend->active = true;
        if ($friend->save()) {
            add($user, $friendId, true);
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public static function refuse($user, $friendId) {
        $friend = App\Models\Friend::where("friend_id", $user->id)
                ->where("user_id", $friendId)
                ->first();
        if ($friend->delete()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }
    
    public function fetch($userId, $includePending = false){

        $rtn = [];
        if ($userId != -1) {
            $user = User::find($userId);
        }
        else {
            $user = User::find(Auth::user()->id);
        }

        if(is_object($user)){

            //Loop through own friends
            foreach($user->friends AS $friend){
                if($friend->pivot->status == "accepted" || ($friend->pivot->status == "pending" && $includePending)) {
                    $rtn[] = [
                        "id" => $user->id,
                        "firstName" => $friend->firstName,
                        "lastName" => $friend->lastName,
                        "status" => $friend->pivot->status,
                        "canAccept" => false,
                        // to add to db
                        "profile_pic_url" => "https://organicthemes.com/demo/profile/files/2012/12/profile_img.png",
                    ];
                }
            }

            //Loop through requested friends
            foreach($user->friends2 AS $friend){
                if($friend->pivot->status == "pending"){
                    $rtn[] = [
                        "id" => $user->id,
                        "firstName" => $friend->firstName,
                        "lastName" => $friend->lastName,
                        "status" => $friend->pivot->status,
                        "canAccept" => true,
                        // to add to db
                        "profile_pic_url" => "https://organicthemes.com/demo/profile/files/2012/12/profile_img.png",
                    ];
                }
            }

        }
        else {
            return (["status" => "refused", "http" => 404]);
        }
        return $rtn;

    }

}