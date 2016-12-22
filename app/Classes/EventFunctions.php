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

    public static function create(SubmitEventCreate $request){

        $data = $request->all();

        if(array_key_exists('location_id', $data)){
            $locationId = $data["location_id"];
        }else{
            $location = Location::create([
                "name" => $data["location_name"],
                "public" => $data["public"],
                "lat" => $data["location_name"],
                "lng" => $data["location_name"],
            ]);
            $locationId = $location->id;
        }

        $create = [
            "name" => $data["name"],
            "user_id" => Auth::user()->id,
            "location_id" => $locationId,
            "public" => $data["public"],
            "type" => $data["type"]
        ];

        if(array_key_exists("expires", $data)) {
            $create["expires"] = $data["expires"];
        }

        //Create record
        $event = Event::create($create);

        return (["status" => "created", "event_id" => $event->id]);

    }

    public static function fetch($id){

        $event = Event::find($id);

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
