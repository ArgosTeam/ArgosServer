<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Photo;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 26/09/2016
 * Time: 6:56 AM
 */


class PhotoFunctions
{

    public static function uploadImage(SubmitUploadPhoto $request){

        $data = $request->all();

        $decode = base64_decode($data["image"]);
        $md5 = md5($decode);

        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)){
            return (["status" => "duplicate", "photo_id" => $photo->id]);
        }

        $path =  'images/' . time() . ".jpg";

        $location = new Location();
        $location->lat = $data["latitude"];
        $location->lng = $data["longitude"];
        $location->save();
        //Create record
        $photo = Photo::create([
            "name" => $data["name"],
            "description" => "",
            "path" => $path,
            "user_id" => Auth::user()->id,
            "location_id" => $location->id,
            "md5" => $md5
        ]);
        
        $full = Image::make($decode)->rotate(-90);
        $avatar = Image::make($decode)->resize(60, 60)->rotate(-90);
        $full = $full->stream()->__toString();
        $avatar = $avatar->stream()->__toString();

        //Upload Photo
        Storage::disk('s3')->put($path, $full, 'public');

        //Upload avatar
        Storage::disk('s3')->put("avatar-" . $path, $avatar, 'public');



//        //Attach groups
//        $groups = explode(',', rtrim(ltrim("[",$data["rights"]), "]"));
//
//        foreach($groups AS $group){
//
//            $grp = Group::find($group);
//
//            if(is_object($grp)) {
//                $photo->groups()->attach($group);
//            }
//        }
//
//        //Attach hashtags
//        $hashTags = explode(',', rtrim(ltrim("[",$data["hashtag"]), "]"));
//
//        foreach($hashTags AS $hashTag){
//            $tag = Hashtag::firstOrCreate(["tag" => $hashTag]);
//            $photo->hashTags()->attach($tag);
//        }


        return (["status" => "created", "photo_id" => $photo->id]);

    }

    public static function fetchPhoto($id) {

        $photo = Photo::join('locations', 'photos.location_id', '=', 'locations.id')
               ->find($id);

        $result = [
            "id" => $photo->id,
            "path" => env('S3_URL') . env('S3_BUCKET') . "/" . $photo->path,
            "name" => $photo->name,
            "lat" => $photo->lat,
            "lng" => $photo->lng,
            "likes" => 0,
        ];

        return $result;

    }

}
