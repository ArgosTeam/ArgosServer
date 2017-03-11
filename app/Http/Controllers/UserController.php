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
        $user_id = $request->input('user_id');
        UserFunctions::follow($user, $user_id);
    }

    public function profile_pic(Request $request) {
        $user = Auth::user();
        return UserFunctions::profile_pic($user,
                                          $request->input('image'));
    }

    public function photos(Request $request) {
        $user = $request->input('id') == -1
              ? Auth::user()
              : User::find($request->input('id'));
        $all = $request->input('id') == -1
             ? true
             : false;
        return UserFunctions::getUserAlbum($user, $all);
    }

    public function session(Request $request) {
        $user = Auth::user();
        return UserFunctions::getSession($user);
    }
}
