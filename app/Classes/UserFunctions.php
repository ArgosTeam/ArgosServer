<?php
/**
 * Created by PhpStorm.
 * User: YaskOne
 * Date: 29/11/2016
 * Time: 7:18 AM
 */

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserFunctions
{
    public function fetch($userId){

        if ($userId != -1) {
            $user = User::find($userId);
        }
        else {
            $user = User::find(Auth::user()->id);
        }

        if (!is_object($user)) {
            return (["status" => "refused", "http" => 404]);
        }
        $result = [
            "id" => $user->id,
            "email" => $user->email,
            "firstName" => $user->firstName,
            "lastName" => $user->lastName,
            "username" => $user->username,
            "phone" => $user->phone,
            "sex" => $user->sex,
            // to add to db
            "profile_pic_url" => "https://organicthemes.com/demo/profile/files/2012/12/profile_img.png",
            "cover_url" => "https://organicthemes.com/demo/profile/files/2012/12/profile_img.png",
        ];

        return $result;
    }

    public function setEmail(){

        $users = User::all();

        foreach($users AS $user) {
            $user->email = $user->phone;
        }
        return $users;
    }

}