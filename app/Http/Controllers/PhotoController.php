<?php

namespace App\Http\Controllers;

use App\Classes\PhotoFunctions;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoController extends Controller
{
    public function upload(Request $request){
        return PhotoFunctions::upload($request);
    }

    public function macro(Request $request) {
        $photo_id = $request->input('id');
        $user = Auth::user();
        return PhotoFunctions::getMacro($user, $photo_id);
    }

    public function comment(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('photo_id');
        $content = $request->input('content');
        PhotoFunctions::comment($user, $photo_id, $content);
    }
}
