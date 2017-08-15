<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Classes\UserFunctions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;

class UserController extends Controller
{

    public function infos(Request $request) {
        $user = Auth::user();
        $id = $request->input('id');
        return UserFunctions::getInfos($user, $id);
    }

    public function follow(Request $request) {
        $user = Auth::user();
        $user_id = $request->input('id');
        return UserFunctions::follow($user, $user_id);
    }

    public function unfollow(Request $request) {
        $user = Auth::user();
        $user_id = $request->input('id');
        return UserFunctions::unfollow($user, $user_id);
    }

    public function profile_pic(Request $request) {
        $user = Auth::user();
        return UserFunctions::profile_pic($user,
                                          $request->input('image'));
    }

    public function photos(Request $request) {
        $userProfile = $request->input('id') == -1
                     ? Auth::user()
                     : User::find($request->input('id'));
        $user = Auth::user();
        return response(['content' => UserFunctions::getUserAlbum($userProfile, $user)], 200);
    }

    public function session(Request $request) {
        $user = Auth::user();
        return UserFunctions::getSession($user);
    }

    public function contacts(Request $request) {
        $user_id = $request->input('id');
        $name_begin = $request->input('name_begin');
        $exclude = $request->input('exclude');
        $user = Auth::user();
        return UserFunctions::getRelatedContacts($user,
                                                 $user_id,
                                                 $name_begin,
                                                 $exclude);
    }

    public function events(Request $request) {
        $user_id = $request->input('id');
        $userProfile = ($user_id == -1
                        ? Auth::user()
                        : User::find($user_id));
        $user = Auth::user();
        $name_begin = $request->input('name_begin');
        $exclude = $request->input('excludes');
        return UserFunctions::events($serProfile,
                                     $user,
                                     $name_begin,
                                     $exclude);
    }

    public function edit(Request $request) {  
        $user = Auth::user();
        return UserFunctions::edit($user, $request->all());
    }
}
