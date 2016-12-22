<?php
namespace App\Classes;
use App\Http\Requests\SubmitEventCreate;
use App\Http\Requests\SubmitLocationCreate;
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


class LocationFunctions
{

    public static function create(SubmitLocationCreate $request){

        $data = $request->all();

        $create = [
            "name" => $data["name"],
            "public" => $data["public"],
            "lat" => $data["lat"],
            "lng" => $data["lng"]
        ];

        //Create record
        $location = Location::create($create);

        return (["status" => "created", "event_id" => $location->id]);

    }

    public static function fetch($id){

        $asset = Location::find($id);

        $result = [
            "id" => $asset->id,
            "name" => $asset->name,
            "lat" => $asset->lat,
            "lng" => $asset->lng
        ];

        return $result;

    }

}
