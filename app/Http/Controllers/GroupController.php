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
        $group_id = $request->input('id');
        return GroupFunctions::join($user, $group_id);
    }

    public function accept_join(Request $request) {
        $user = Auth::user();
        $user_id = $request->input('user_id');
        $group_id = $request->input('group_id');
        return GroupFunctions::acceptPrivateJoin($user, $user_id, $group_id);
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

    public function photos(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        return GroupFunctions::photos($user, $group_id);
    }

    public function invite(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('group_id');
        $users_id = $request->input('users_id');
        return GroupFunctions::invite($user, $group_id, $users_id);
    }

    public function refuse_invite(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        return GroupFunctions::refuseInvite($user, $group_id);
    }

    public function accept_invite(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        return GroupFunctions::acceptInvite($user, $group_id);
    }

    public function link(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        $invites = $request->input('invites');
        return GroupFunctions::link($user, $group_id, $invites);
    }

    public function comment(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        $content = $request->input('content');
        return GroupFunctions::comment($user, $group_id, $content);
    }

    public function quit(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        return GroupFunctions::quit($user, $group_id);
    }

    public function edit(Request $request) {
        $user = Auth::user();
        $data = $request->all();
        return GroupFunctions::edit($user, $data);
    }

    public function contacts(Request $request) {
        $group_id = $request->input('id');
        $name_begin = $request->input('name_begin');
        $exclude = $request->input('exclude');
        $user = Auth::user();
        return GroupFunctions::getRelatedContacts($user,
                                                  $group_id,
                                                  $name_begin,
                                                  $exclude);
    }

    public function events(Request $request) {
        $group_id = $request->input('id');
        $user = Auth::user();
        $name_begin = $request->input('name_begin');
        $exclude = $request->input('excludes');
        return GroupFunctions::events($user,
                                      $group_id,
                                      $name_begin,
                                      $exclude);
    }

    public function unlink(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        $unlinks = $request->input('unlinks');
        return GroupFunctions::unlink($user, $group_id, $unlinks);
    }
}
