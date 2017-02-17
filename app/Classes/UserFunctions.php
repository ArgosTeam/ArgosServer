<?php

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Friend;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserFunctions
{

    public static function getInfos($user, $id) {
        $idToSearch = ($id == -1 ? $user->id : $id);

        $userProfile = User::find($idToSearch);
        $friendShip = Friend::where('user_id', '=', $user->id)
                    ->where('friend_id', '=', $userProfile->id)
                    ->first();
        $response = [];
        $response['id'] = $userProfile->id;
        $response['nickname'] = '';
        $response['profile_pic'] = '';
        $response['name'] = $userProfile->firstName;
        $response['surname'] = $userProfile->lastName;
        $response['university'] = '';
        $response['master'] = '';
        $response['stats'] = '';
        if (is_object($friendShip)) {
            $response['friend'] = $friendShip->active;
            $response['pending'] = !$friendShip->active;
            $response['own'] = $friendShip->own;
        } else {
            $response['friend'] = false;
            $response['pending'] = false;
            $response['own'] = false;
        }
        
        return response($response, 200);
    }

    public static function follow($user, $user_id) {
        if (is_object(User::find($user_id))) {
            $user->followed()->attach($user_id);
            return response('Succes', 200);
        } else {
            return response('User does not exist', 404);
        }
    }

    public static function profile_pic($user, $encode) {
        $decode = base64_decode($encode);
        $md5 = md5($decode);

        /*
        ** Check photo already exists
        */
        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)) {
            return response(['refused' => 'Photo already exists'], 404);
        }

        $photo = PhotoFunctions::uploadImage($user, $md5, $decode);
        $photo->save();

        $user->profile_pic()->associate($photo);
        $user->save();

        return response(['photo_id' => $photo->id], 200);
    }

    public static function getUserAlbum($user, $all) {
        $photos = $user->photos();
        if (!$all) {
            $photos->where('public', '=', true);
        }
        $photos = $photos->get();
        $response = [];
        foreach ($photos as $photo) {

            // Get signed url from s3
            $s3 = Storage::disk('s3');
            $client = $s3->getDriver()->getAdapter()->getClient();
            $expiry = "+10 minutes";
            
            $command = $client->getCommand('GetObject', [
                'Bucket' => env('S3_BUCKET'),
                'Key'    => "avatar-" . $photo->path,
            ]);
            $request = $client->createPresignedRequest($command, $expiry);
            
            $response[] = [
                'photo_id' => $photo->id,
                'lat' => $photo->location->lat,
                'lng' => $photo->location->lng,
                'description' => $photo->description,
                'path' => '' . $request->getUri() . '',
                'public' => $photo->public
            ];
        }
        return response($response, 200);
    }
}