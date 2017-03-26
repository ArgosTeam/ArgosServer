<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Classes\SearchFunctions;

class SearchController extends Controller
{

    public function events(Request $request) {
        $user_id = $request->input('id') == -1
                 ? Auth::user()->id
                 : $request->input('id');
        $user = User::find($user_id);
        $nameBegin = $request->input("name_begin");
        $knownOnly = $request->input("known_only");
        return SearchFunctions::events($user, $nameBegin, $knownOnly);
    }

    public function photos(Request $request) {
        $user_id = $request->input('id') == -1
                 ? Auth::user()->id
                 : $request->input('id');
        $user = User::find($user_id);
        $nameBegin = $request->input("name_begin");
        return SearchFunctions::photos($user, $nameBegin);
    }

    public function search(Request $request) {
        $user = Auth::user();
        $data = $request->all();
        return SearchFunctions::globalSearch($user, $data);
    }
}
