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
        $user = User::find(Auth::user()->id);
        $nameBegin = $request->input("name_begin");
        $knownOnly = $request->input("known_only");
        return SearchFunctions::getContacts($user, $nameBegin, $knownOnly);
    }
}
