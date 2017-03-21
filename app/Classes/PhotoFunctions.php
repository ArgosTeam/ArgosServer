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
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPublicPicture;
use App\Notifications\NewPrivatePicture;
use App\Models\User;

class PhotoFunctions
{

    public static function getUrl(Photo $photo, $type = "avatar") {
        // Get signed url from s3
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";

        $key = '';
        switch ($type) {
          case "avatar":
              $key = "avatar-" . $photo->path;
              break ;
          case "regular":
              $key = "regular-" . $photo->path;
              break;
          case "macro":
              $key = $photo->path;
              break;
        }
        $command = $client->getCommand('GetObject', [
            'Bucket' => env('S3_BUCKET'),
            'Key'    => $key,
        ]);
        $request = $client->createPresignedRequest($command, $expiry);
        return $request;
    }
    
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
        $full = Image::make($image);
        $avatar = Image::make($image)->resize(60, 60);
        $regular = Image::make($image)->resize(120, 120);
        $full = $full->stream()->__toString();
        $avatar = $avatar->stream()->__toString();
        $regular = $regular->stream()->__toString();

        //Upload Photo
        Storage::disk('s3')->put($path, $full, 'public');

        //Upload avatar
        Storage::disk('s3')->put('avatar-' . $path, $avatar, 'public');
        
        //Upload Regular size
        Storage::disk('s3')->put('regular-' . $path, $regular, 'public');
        
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
            return response(['refused' => 'Photo already exists'], 403);
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

        $ids = array_key_exists('rights', $data) ? $data['rights'] : [];
        $users_to_share = User::whereIn('id', $ids)->get();

        foreach ($users_to_share as $shared) {
            $shared->photos()->attach($photo->id, [
                'admin' => false
            ]);
        }
        if ($photo->public) {
            $user->notify(new NewPublicPicture($user, $photo, 'slack'));
            foreach ($user->followers()->get() as $follower) {
                $follower->notify(new NewPublicPicture($user, $photo, 'database'));
            }
        } else {
            $user->notify(new NewPrivatePicture($user, $photo, 'slack'));
        }

        if (!empty($users_to_share)) {
            Notification::send($users_to_share, new NewPrivatePicture($user, $photo, 'database'));
        }
        
        return (response(['photo_id' => $photo->id], 200));
    }

    public static function getInfos($user, $photo_id) {
        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response('Photo not found', 403);
        }

        $request = PhotoFunctions::getUrl($photo, 'macro');

        /*
        ** Return Data with requested parameters
        */
        $originUser = User::find($photo->origin_user_id);
        $profile_pic_path = null;
        if (is_object($profile_pic = $originUser->profile_pic()->first())) {
            $requestOrigin = PhotoFunctions::getUrl($profile_pic);
            $profile_pic_path = '' . $requestOrigin->getUri() . '';
        }
        $data = [
            'id' => $photo->id,
            'url' => '' . $request->getUri() . '',
            'description' => $photo->description,
            'admin_url' => $profile_pic_path,
            'admin_id' => $originUser->id,
            'admin_nickname' => $originUser->nickname
        ];

        return response($data, 200);
    }

    public static function comment($user, $photo_id, $content) {
        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response(['status' => 'Photo does not exist'], 403);
        }
        $comment = new Comment();
        $comment->content = $content;
        $comment->user()->associate($user);
        if ($comment->save()) {
            $comment->photos()->attach($photo->id);
            return response(['comment_id' => $comment->id], 200);
        } else {
            return response(['status' => 'Error while saving'], 403);
        }
    }

    public static function getRelatedContacts($user,
                                              $photo_id,
                                              $known_only,
                                              $name_begin,
                                              $exclude) {
        $photo = Photo::find($photo_id);

        $groups = $photo->groups();
        $users = $photo->users();
        if ($known_only) {
            $groups->whereIn('groups.id',
                             $user->groups()
                             ->where('status', 'accepted')
                             ->get()
                             ->pluck('id'));
            $users->whereIn('users.id',
                            $user->getFriends()
                            ->get()
                            ->pluck('id'));
        }

        if ($name_begin) {
            $groups->where('name', 'like', '%' . $name_begin);
            $users->where('nickname', 'like', '%' . $name_begin);
        }

        $groups = $groups->get();
        $users = $users->get();
        
        if (is_object($photo)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $group) {
                $profile_pic_path = null;
                $profile_pic = $group->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $request = PhotoFunctions::getUrl($profile_pic);
                    $profile_pic_path = '' . $request->getUri() . '';
                }
                $response['groups'][] = [
                    'id' => $group->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $group->name,
                    'is_contact' => ($group->users->contains($user->id)
                                     ? true : false)
                ];
            }

            foreach ($users as $contact) {
                $profile_pic_path = null;
                $profile_pic = $contact->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $request = PhotoFunctions::getUrl($profile_pic);
                    $profile_pic_path = '' . $request->getUri() . '';
                }

                $firstname = null;
                $lastname = null;
                $is_contact = false;
                if ($user->getFriends->contains($user->id)) {
                    $firstname = $contact->firstname;
                    $lastname = $contact->lastname;
                    $is_contact = true;
                }
                
                $response['users'][] = [
                    'id' => $contact->id,
                    'profile_pic' => $profile_pic_path,
                    'nickname' => $contact->nickname,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'is_contact' => $is_contact
                ];
            }

            
        }
        return response(['status' => 'Photo does not exists']);
    }
}
