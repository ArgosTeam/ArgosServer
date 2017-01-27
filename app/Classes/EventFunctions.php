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

        $location = Location::create([
            "lat" => $data["lat"],
            "lng" => $data["lng"],
        ]);
        $locationId = $location->id;

        $event = new Event();
        $event->name = $data["name"];
        $event->user_id = Auth::user()->id;
        $event->location_id = $locationId;
        $event->public = $data["public"];

        if(array_key_exists("expires", $data)) {
            $event->expires = $data["expires"];
        }

        if ($event->save()) {
            return (["status" => "created", "event_id" => $event->id]);
        } else {
            return (["status" => "error while saving event"]);
        }
    }

    public static function fetch($id){

        $event = Event::leftJoin('locations', 'events.location_id', '=', 'locations.id')
               ->find($id)
               ->first();

        $result = [
            "id" => $event->id,
            "name" => $event->name,
            "lat" => $event->lat,
            "lng" => $event->lng,
            "likes" => 0,
        ];

        return $result;

    }

}
