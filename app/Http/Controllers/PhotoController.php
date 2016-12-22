<?php

namespace App\Http\Controllers;

use App\Classes\PhotoFunctions;
use App\Models\Photo;
use Illuminate\Http\Request;

use App\Http\Requests;

class PhotoController extends Controller
{
    //

    public function uploadPhoto(Requests\SubmitUploadPhoto $request){

        return PhotoFunctions::uploadImage($request);
    }

    public function fetchPhotos($id){

        return PhotoFunctions::fetchPhoto($id);

    }
}
