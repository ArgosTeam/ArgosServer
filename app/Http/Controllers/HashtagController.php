<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HashtagController extends Controller {

    public function trendings(Request $request) {
        $hashtags = Hashtag::orderBy('count', 'desc')
                  ->limit(env('GLOBAL_SEARCH_COUNT'))
                  ->get();

        return response(["content" => $hashtags], 200);
    }
}