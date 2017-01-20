<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Photo;
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

        //Create record
        $photo = Photo::create([
            "name" => $data["name"],
            "description" => "",
            "path" => $path,
            "user_id" => Auth::user()->id,
            "lat" => $data["latitude"],
            "lng" => $data["longitude"],
            "md5" => $md5
        ]);

        Storage::disk('s3')->put('myfile.txt', 'tototata', 'public');
        
        \Illuminate\Support\Facades\Log::info('DEBUG : ' . $decode);
        
        $full = Image::make($decode)->rotate(-90);
        
        \Illuminate\Support\Facades\Log::info('DEBUG2 : ' . $decode);
        $avatar = Image::make($decode)->resize(60, 60)->rotate(-90);
        
        \Illuminate\Support\Facades\Log::info('DEBUG3 : ' . $decode);
        $full = $full->stream()->__toString();
        
        \Illuminate\Support\Facades\Log::info('DEBUG4 : ' . $decode);
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

    public static function fetchPhoto($id){

        $photo = Photo::find($id);

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
