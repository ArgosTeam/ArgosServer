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
        $user = Auth::user();
        $friend_id = $request->input("id");
        $friend = User::find($friend_id);
        FriendFunctions::add($friend, $user);
        $response = FriendFunctions::add($user, $friend, true);
        return $response;
     }

    public function accept(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input("id");
        $friend = User::find($friend_id);
        FriendFunctions::accept($friend, $user, true);
        return FriendFunctions::accept($user, $friend);
    }

    public function refuse(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input("id");
        $friend = User::find($friend_id);
        FriendFunctions::refuse($friend, $user, true);
        return FriendFunctions::refuse($user, $friend);
    }

    public function cancel(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input("id");
        $friend = User::find($friend_id);
        FriendFunctions::cancel($friend, $user);
        return FriendFunctions::cancel($user, $friend);
    }

    public function remove(Request $request) {
        $user = Auth::user();
        $friend_id = $request->friend('id');
        $friend = User::find($friend_id);
        FriendFunctions::delete($friend, $user);
        return FriendFunctions::delete($user, $friend);
    }

    public function favorites(Request $request) {
        $user = Auth::user();
        return FriendFunctions::getFavorites($user);
    }
}
