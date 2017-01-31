<?php
namespace App\Classes;
use App\Http\Requests\SubmitEventCreate;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Event;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Location;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 26/09/2016
 * Time: 6:56 AM
 */


class EventFunctions
{

    public static function add(Request $request){

        $data = $request->all();


        $event = Event::where('name', '=', $data['name'])
               ->first();
        if (is_object($event)) {
            return response('Event alreay exists', 404);
        }
        
        $location = new Location([
            "lat" => $data["lat"],
            "lng" => $data["lng"],
        ]);

        $event = new Event();
        $event->name = $data["name"];
        $event->user_id = Auth::user()->id;
        $event->public = $data["public"];

        if(array_key_exists("expires", $data)) {
            $event->expires = $data["expires"];
        }

        if ($event->save()) {
            $event->location()->associate($location);
            return (["status" => "created", "event_id" => $event->id]);
        } else {
            return (["status" => "error while saving event"]);
        }
    }

}
