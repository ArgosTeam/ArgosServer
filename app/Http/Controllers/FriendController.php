<?php

namespace App\Http\Controllers;

use App\Classes\FriendFunctions;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Friend;

class FriendController extends Controller
{
   
    public function add(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        return FriendFunctions::add($user, $friendId);
     }

    public function accept(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        return FriendFunctions::accept($user, $friendId);
    }

    public function refuse(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        return FriendFunctions::refuse($user, $friendId);
    }

    public function delete(Request $request) {
        $friend = Friend::find($request->input["id"]);
        if ($friend->delete()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }
}
