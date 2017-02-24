<?php

namespace App\Http\Controllers;

use App\Classes\FriendFunctions;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FriendController extends Controller
{
   
    public function add(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        FriendFunctions::add($friend, $user);
        $response = FriendFunctions::add($user, $friend, true);
        return $response;
     }

    public function accept(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        FriendFunctions::accept($friend, $user);
        return FriendFunctions::accept($user, $friend, true);
    }

    public function refuse(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        FriendFunctions::refuse($friend, $user);
        return FriendFunctions::refuse($user, $friend, true);
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
