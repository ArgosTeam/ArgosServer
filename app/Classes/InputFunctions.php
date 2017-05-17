<?php

namespace App\Classes;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\Event;
use App\Models\Photo;

class InputFunctions
{

    // Dashes are illegal chars for hashtags, underscores are allowed.
    public static function parse($elem, $description) {
        $hashtags = [];
        preg_match_all("/(#\w+)/", $description, $hashtags);

        foreach ($hashtags as $name) {
            $hashtag = Hashtag::where('name', $name)
                         ->first();
            if (!is_object($hashtag)) {
                $hashtag = new Hashtag();
                $hashtag->name = $name;
                $hashtag->save();
            }
            $elem->hashtags()->attach($hashtag->id);
        }
    }
}