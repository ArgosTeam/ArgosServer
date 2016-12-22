<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;

use App\Http\Requests;

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
}
