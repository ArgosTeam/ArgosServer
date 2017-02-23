<?php

namespace App\Http\Controllers;

use App\Classes\fetchFunctions;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;

class FetchController extends Controller
{

    public function fetch(Request $request) {
        $data = $request->input();
        return response(fetchFunctions::fetch($data));
    }
}
