<?php

namespace App\Http\Controllers;

use App\Classes\EventFunctions;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;

class EventController extends Controller
{

    public function add(Request $request){
        $user = Auth::user();
        return EventFunctions::add($user, $request);
    }

    public function join(Request $request){
        $user = Auth::user();
        $event_id = $request->input('event_id');
        return EventFunctions::join($user, $event_id);
    }

    public function accept(Request $request){
        $user = Auth::user();
        $user_id = $request->input('user_id');
        $event_id = $request->input('event_id');
        return EventFunctions::accept($user, $user_id, $event_id);
    }

    // public function refuse(Request $request){
    //     $user = Auth::user();
    //     $user_id = $request->input('user_id');
    //     $event_id = $request->input('event_id');
    //     return EventFunctions::refuse($user, $user_id, $event_id);
    // }
}
