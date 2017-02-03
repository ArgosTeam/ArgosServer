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

        
        $userProfile = User::select(['users.*', 'user_users.friend_id', 'user_users.active', 'user_users.own'])
                     ->leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
                     ->where('users.id', '=', $idToSearch)
                     ->whereNull('user_users.user_id')
                     //->orWhere('users.id', '=', $idToSearch)
                     //->where('user_users.friend_id', '=', $user->id)
                     ->get();
        Log::info('DEBUG : ' . print_r($userProfile, true));
        $response = [];
        $response['id'] = $userProfile->id;
        $response['nickname'] = '';
        $response['profile_pic'] = '';
        $response['name'] = $userProfile->firstName;
        $response['surname'] = $userProfile->lastName;
        $response['university'] = '';
        $response['master'] = '';
        $response['stats'] = '';
        if ($userProfile->active !== null) {
            $response['friend'] = $userProfile->active;
            $response['pending'] = !$userProfile->active;
            $response['own'] = $userProfile->own;
        } else {
            $response['friend'] = false;
            $response['pending'] = false;
            $response['own'] = false;
        }

        return response($response, 200);
    }

}