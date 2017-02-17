<?php

namespace App\Http\Controllers;

use App\Classes\GroupFunctions;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{

    public function add(Request $request) {
        $user = Auth::user();
        return GroupFunctions::add($user, $request);
    }

    public function join(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('group_id');
        return GroupFunctions::join($user, $group_id);
    }

    public function accept(Request $request) {
        $user = Auth::user();
        $user_id = $request->input('user_id');
        $group_id = $request->input('group_id');
        return GroupFunctions::accept($user, $user_id, $group_id);
    }

    public function infos(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        return GroupFunctions::infos($user, $group_id);
    }

    public function profile_pic(Request $request) {
        $user = Auth::user();
        return GroupFunctions::profile_pic($user,
                                          $request->input('image'),
                                          $request->input('group_id'));
    }

    public function link_photo(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('photo_id');
        $group_id = $request->input('group_id');
        return GroupFunctions::link_photo($user, $photo_id, $group_id);
    }
}
