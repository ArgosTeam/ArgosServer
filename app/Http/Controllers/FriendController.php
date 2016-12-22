<?php

namespace App\Http\Controllers;

use App\Classes\FriendFunctions;
use Illuminate\Http\Request;

use App\Http\Requests;

class FriendController extends Controller
{
    //
    public function fetchRequests($userId, $incPending = false){

        $func = new FriendFunctions;
        return $func->fetch($userId, $incPending);

    }

    public function createRequest(Requests\FriendActionRequest $request){

        $data = $request->all();
        $func = new FriendFunctions;
        return $func->request($data["userId"], $data["friendId"]);
        
    }

    public function acceptRequest(Requests\FriendActionRequest $request){

        $data = $request->all();
        $func = new FriendFunctions;
        return $func->accept($data["userId"], $data["friendId"]);

    }

    public function declineRequest(Requests\FriendActionRequest $request){

        $data = $request->all();
        $func = new FriendFunctions;
        return $func->decline($data["userId"], $data["friendId"]);

    }


}
