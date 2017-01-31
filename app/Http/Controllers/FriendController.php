<?php

namespace App\Http\Controllers;

use App\Classes\FriendFunctions;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Friend;

class FriendController extends Controller
{
   
    public function add(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        Log::info('userId :' . $friendId);
        FriendFunctions::add($friend, $user->id);
        return FriendFunctions::add($user, $friendId, true);
     }

    public function accept(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        FriendFunctions::accept($friend, $user->id);
        return FriendFunctions::accept($user, $friendId);
    }

    public function refuse(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $friend = User::find($friendId);
        FriendFunctions::refuse($friend, $user->id);
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
