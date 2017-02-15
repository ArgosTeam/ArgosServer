<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class PhotoFunctions
{
    
    public static function uploadImage($user, $md5, $image) {
        /*
        ** Create new Photo
        */
        $path =  'images/' . time() . '.jpg';
        $photo = new Photo();
        $photo->path = $path;
        $photo->origin_user_id = $user->id;
        $photo->md5 = $md5;

        /*
        ** Upload through storage -> AWS S3
        */
        $full = Image::make($image)->rotate(-90);
        $avatar = Image::make($image)->resize(60, 60)->rotate(-90);
        $full = $full->stream()->__toString();
        $avatar = $avatar->stream()->__toString();

        //Upload Photo
        Storage::disk('s3')->put($path, $full, 'public');

        //Upload avatar
        Storage::disk('s3')->put('avatar-' . $path, $avatar, 'public');
        
        return $photo;
    }
    
    public static function uploadUserImage($data) {
        $user = Auth::user();

        $decode = base64_decode($data['image']);
        $md5 = md5($decode);

        /*
        ** Check photo already exists
        */
        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)) {
            return response(['refused' => 'Photo already exists'], 404);
        }
        
        $photo = PhotoFunctions::uploadImage($user, $md5, $decode);
        $photo->public = $data['public'];
        $photo->mode = $data['mode'];
        $photo->description = $data['description'];
        
        /*
        ** Create new location, each upload image from user is geolocalised
        */
        $location = new Location();
        $location->lat = $data['latitude'];
        $location->lng = $data['longitude'];
        $location->save();

        /*
        ** Associate location to photo
        */
        $photo->location()->associate($location);
        $photo->save();

        /*
        ** Create hashtag if not exist
        ** Associate hashtag to photo
        */
        if (is_array($data['hashtags'])) {
            foreach ($data['hashtags'] as $name) {
                $hashtag = Hashtag::where('name', '=', $name)
                         ->first();
                if (!is_object($hashtag)) {
                    $hashtag = Hashtag::create([
                        'name' => $name
                    ]);
                }
                $hashtag->photos()->attach($photo->id);
            }
        }

        /*
        ** Link user to photo
        */
        $user->photos()->attach($photo->id, [
            'admin' => true
        ]);

        return (response(['photo_id' => $photo->id], 200));
    }

    public static function getMacro($user, $photo_id) {
        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response('Photo not found', 404);
        }

        /*
        ** Get signed url from s3
        */
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";
        
        $command = $client->getCommand('GetObject', [
            'Bucket' => env('S3_BUCKET'),
            'Key'    => $photo->path,
        ]);
        $request = $client->createPresignedRequest($command, $expiry);

        $hashtags = [];
        foreach ($photo->hashtags()->get() as $hashtag) {
            $hashtags[] = [
                'id' => $hashtag->id,
                'name' => $hashtag->name
            ];
        }

        /*
        ** Get Comments related to Photo
        */
        $comments = [];
        foreach($photo->comments()->get() as $comment) {
            $comments[] = [
                'content' => $comment->content,
                'user_id' => $comment->user_id
            ];
        }

        /*
        ** Return Data with requested parameters
        */
        $data = [
            'id' => $photo->id,
            'url' => '' . $request->getUri() . '',
            'description' => $photo->description,
            'hashtags' => $hashtags,
            'comments' => $comments,
            'rights' => []
        ];

        return response($data, 200);
    }

    public static function comment($user, $photo_id, $content) {
        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response('Photo does not exist', 404);
        }
        $comment = new Comment();
        $comment->content = $content;
        $comment->user()->associate($user);
        if ($comment->save()) {
            $comment->photos()->attach($photo->id);
            return response(['comment_id' => $comment->id], 200);
        } else {
            return response(['status' => 'Error while saving'], 404);
        }
    }

}
