<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;
use App\Classes\SearchFunctions;

class SearchController extends Controller
{
    //

    public function process(Request $request){

        $search = "%" . $request->q . "%";

        $hashTags = Hashtag::where('name', 'LIKE', $search)->take(10)->get();

        $data = [];
        foreach ($hashTags as $tag) {
            $tempArray = [];
            $tempArray["id"] = $tag->id;
            $tempArray["text"] = strtolower($tag->name);
            $data["results"][] = $tempArray;
        }

        return $data;

    }

    public function selectData() {
        return DropdownFunctions::generalSelect();
    }

    public function contacts(Request $request) {
        $user_id = $request->input('id') == -1
                 ? Auth::user()->id
                 : $request->input('id');
        $user = User::find($user_id);
        $nameBegin = $request->input("name_begin");
        $knownOnly = $request->input("known_only");
        return SearchFunctions::getContacts($user, $nameBegin, $knownOnly);
    }

    public function events(Request $request) {
        $user = User::find(Auth::user()->id);
        $nameBegin = $request->input("name_begin");
        $knownOnly = $request->input("known_only");
        return SearchFunctions::events($user, $nameBegin, $knownOnly);
    }
}
