<?php

namespace App\Http\Controllers;

use App\Classes\LocationFunctions;
use Illuminate\Http\Request;

use App\Http\Requests;

class LocationController extends Controller
{
    //

    public function fetch($id){

        return LocationFunctions::fetch($id);

    }

    public function create(Requests\SubmitLocationCreate $request){

        return LocationFunctions::create($request);

    }

}
