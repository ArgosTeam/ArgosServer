<?php

namespace App\Http\Controllers;

use App\Classes\EventFunctions;
use App\Models\Event;
use Illuminate\Http\Request;

use App\Http\Requests;

class EventController extends Controller
{
    //

    public function fetch($id){

        return EventFunctions::fetch($id);

    }

    public function create(Requests\SubmitEventCreate $request){

        return EventFunctions::create($request);

    }
}
