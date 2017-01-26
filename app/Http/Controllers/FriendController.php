<?php

namespace App\Http\Controllers;

use App\Classes\FriendFunctions;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Models\Friend;

class FriendController extends Controller
{
    public function add(Request $request) {
        $user = User::find(Auth::user()->id);
        $friendId = $request->input("user_id");
        $level = $request->input("level");

        $friend = new App\Models\Friend();
        $friend->user_id = $user->id;
        $friend->friend_id = $friendId;
        $friend->level = $level;
        $friend->active = false;
        if ($friend->save()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public function accept(Request $request) {
        $user = User::find(Auth::user()->id);
        $friend = App\Models\Friend::where('friend_id' => $user->id);
        $friend->active = true;
        if ($friend->save()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public function refuse(Request $request) {
        $user = User::find(Auth::user()->id);
        $friend = App\Models\Friend::where('friend_id' => $user->id);
        if ($friend->delete()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }

    public function delete(Request $request) {
        $friendRequest = App\Models\Friend::where('friend_id' => $request->input['user_id']);
        if ($friend->delete()) {
            return ["status" => "success", "http" => 200];
        } else {
            return ["status" => "refused", "http" => 404];
        }
    }
}
