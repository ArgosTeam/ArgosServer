<?php

namespace App\Http\Controllers;

use App\Classes\PhotoFunctions;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
    public function uploadUserImage(Request $request){
        $data = $request->all();
        return PhotoFunctions::uploadUserImage($data);
    }

    public function infos(Request $request) {
        $photo_id = $request->input('id');
        $user = Auth::user();
        return PhotoFunctions::getInfos($user, $photo_id);
    }

    public function contacts(Request $request) {
        $photo_id = $request->input('id');
        $name_begin = $request->input('name_begin');
        $exclude = $request->input('exclude');
        $user = Auth::user();
        return PhotoFunctions::getRelatedContacts($user,
                                                  $photo_id,
                                                  $name_begin,
                                                  $exclude);
    }

    public function edit(Request $request) {
        $user = Auth::user();
        $data = $request->all();
        return PhotoFunctions::edit($user, $data);
    }

    public function link(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $invites = $request->input('invites');
        return PhotoFunctions::link($user, $photo_id, $invites);
    }

    public function unlink(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $unlinks = $request->input('unlinks');
        return PhotoFunctions::unlink($user, $photo_id, $unlinks);
    }

    // Link to album (photos-users)
    public function follow(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        return PhotoFunctions::follow($user, $photo_id);
    }

    public function unfollow(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        return PhotoFunctions::unfollow($user, $photo_id);
    }

    /*
    ** Handle of photo zoned
    */
    public function unlockPicture(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $userPos = [];
        $userPos[0] = $request->input('lat');
        $userPos[1] = $request->input('lng');
        return PhotoFunctions::unlockPicture($user, $photo_id, $userPos);
    }
}
