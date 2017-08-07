<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HashtagController extends Controller {

    public function trendings(Request $request) {
        $name_begin = $request->input('name_begin');
        $hashtags = Hashtag::orderBy('count', 'desc')
                  ->where('name', 'like', $name_begin . '%')
                  ->limit(env('GLOBAL_SEARCH_COUNT'))
                  ->get();

        return response(["content" => $hashtags], 200);
    }
}