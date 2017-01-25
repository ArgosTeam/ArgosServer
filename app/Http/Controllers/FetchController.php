<?php

namespace App\Http\Controllers;

use App\Classes\fetchFunctions;
use Illuminate\Http\Request;

use App\Http\Requests;

class FetchController extends Controller
{
    //

    public function fetch(){

        $f = new fetchFunctions();
        return response($f->fetch());

    }
}
