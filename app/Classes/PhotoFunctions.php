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

    public static function upload(SubmitUploadPhoto $request){

        $data = $request->all();
        $user = Auth::user();
        $decode = base64_decode($data['image']);
        $md5 = md5($decode);

        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)){
            return response('Photo already exists', 404);
        }

        $path =  'images/' . time() . '.jpg';

        $location = new Location();
        $location->lat = $data['latitude'];
        $location->lng = $data['longitude'];
        $location->save();

        
        $photo = new Photo();
        $photo->name = $data['name'];
        $photo->description = $data['description'];
        $photo->path = $path;
        $photo->public = $data['public'];
        $photo->hashtags = '';
        $photo->mode = $data['mode'];
        $photo->origin_user_id = $user->id;
        $photo->md5 = $md5;
        $photo->location()->associate($location);
        $photo->save();

        $user->photos()->attach($photo->id, [
            'admin' => true
        ]);
        
        $full = Image::make($decode)->rotate(-90);
        $avatar = Image::make($decode)->resize(60, 60)->rotate(-90);
        $full = $full->stream()->__toString();
        $avatar = $avatar->stream()->__toString();

        //Upload Photo
        Storage::disk('s3')->put($path, $full, 'public');

        //Upload avatar
        Storage::disk('s3')->put('avatar-' . $path, $avatar, 'public');

        return (response(['photo_id' => $photo->id], 200));
    }

}
