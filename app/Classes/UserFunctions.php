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
use Illuminate\Support\Facades\Log;

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

    public static function getInfos($user, $id) {
        $idToSearch = ($id == -1 ? $user->id : $id);

        $user = User::select('users.*', 'user_users.own', 'user_users.active')
              ->leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
              ->where('user_users.friend_id', '=', $user->id)
              ->where('users.id', '=', $idToSearch)
              ->first();
        Log::info('DEBUG : ' . print_r($user, true));
        $response = [];
        $response['id'] = $user->id;
        $response['nickname'] = '';
        $response['profile_pic'] = '';
        $response['name'] = $user->firstName;
        $response['surname'] = $user->lastName;
        $response['university'] = '';
        $response['master'] = '';
        $response['stats'] = '';
        if ($user->active !== null) {
            $response['friend'] = $user->active;
            $response['pending'] = !$user->active;
            $response['own'] = $user->own;
        } else {
            $response['friend'] = false;
            $response['pending'] = false;
            $response['own'] = false;
        }

        return response($response, 200);
    }

}