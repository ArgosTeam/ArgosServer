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

    public function comment(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $content = $request->input('content');
        PhotoFunctions::comment($user, $photo_id, $content);
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
}
