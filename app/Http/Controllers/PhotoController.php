<?php

namespace App\Http\Controllers;

use App\Classes\PhotoFunctions;
use App\Models\Photo;
use Illuminate\Http\Request;

use App\Http\Requests;

class PhotoController extends Controller
{
    public function upload(Request $request){
        return PhotoFunctions::upload($request);
    }
}
