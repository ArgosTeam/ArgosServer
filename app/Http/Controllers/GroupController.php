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
        $public = $request->input('public');
        $name = $request->input('name');
        return GroupFunctions::add($user, $public, $name);
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

}
