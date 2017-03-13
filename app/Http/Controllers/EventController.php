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

    public function accept_join(Request $request){
        $user = Auth::user();
        $user_id = $request->input('user_id');
        $event_id = $request->input('event_id');
        return EventFunctions::acceptPrivateJoin($user, $user_id, $event_id);
    }

    public function invite(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('event_id');
        $users_id = $request->input('users_id');
        return EventFunctions::invite($user, $event_id, $users_id);
    }

    public function accept_invite(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('event_id');
        return EventFunctions::acceptInvite($user, $event_id);
    }

    public function refuse(Request $request){
        $user = Auth::user();
        $user_id = $request->input('user_id');
        $event_id = $request->input('event_id');
        return EventFunctions::refuse($user, $user_id, $event_id);
    }

    public function infos(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('id');
        return EventFunctions::infos($user, $event_id);
    }

    public function comment(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('event_id');
        $content = $request->input('content');
        return EventFunctions::comment($user, $event_id, $content);
    }

    public function profile_pic(Request $request) {
        $user = Auth::user();
        return EventFunctions::profile_pic($user,
                                           $request->input('image'),
                                           $request->input('event_id'));
    }

    public function link_photo(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('photo_id');
        $events_id = $request->input('events_id');
        return EventFunctions::link_photo($user, $photo_id, $events_id);
    }

    public function photos(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('event_id');
        return EventFunctions::photos($user, $event_id);
    }

    /*
    ** Invite Users from all groups in groups_id
    ** if user belongs to group
    */
    public function link_groups(Request $request) {
        $user = Auth::user();
        $groups_id = $request->input('groups_ids');
        $event_id = $request->input('event_id');
        return EventFunctions::link_groups($user, $groups_id, $event_id);
    }
}
