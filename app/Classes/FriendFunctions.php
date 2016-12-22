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

class FriendFunctions
{

    //UserId is the user requesting
    public function request($userId, $friendId){

        $user = User::find($userId);
        if (is_object($user)) {
            $user->friends->attach($friendId, ["status" => "pending"]);
        }
        else {
            return (["status" => "refused", "http" => 404]);
        }
        return (["status" => "success", "http" => 200]);

    }

    //User Id and friendId are the reverse the the request function
    public function accept($userId, $friendId){

        $user = User::find($userId);
        if(is_object($user)){
            $user->friends2->updateExistingPivot($friendId, ["status" => "accepted"]);
        }else{
            return (["status" => "refused", "http" => 404]);
        }

        return (["status" => "success", "http" => 200]);

    }

    //User Id and friendId are the reverse the the request function
    public function decline($userId, $friendId){

        $user = User::find($userId);
        if(is_object($user)){
            $user->friends2->updateExistingPivot($friendId, ["status" => "declined"]);
        }else{
            return (["status" => "refused", "http" => 404]);
        }

        return (["status" => "success", "http" => 200]);

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