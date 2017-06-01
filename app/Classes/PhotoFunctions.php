<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Group;
use App\Models\Photo;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPublicPicture;
use App\Notifications\NewPrivatePicture;
use App\Models\User;
use App\Models\Channel;
use App\Classes\GeoTools;
use App\Models\RatingType;
use App\Models\PhotoRating;

class PhotoFunctions
{

    public static function getUrl($photo, $type = "avatar") {
        // Get signed url from s3
        $user = Auth::user();
        $path = null;
        if (!is_object($photo)) {
            return null;
        }
        $allow = ($photo->mode == 'normal'
                  || $photo->mode == null
                  || $user->isUnlocked($photo->id));
        if ($allow) {
            $s3 = Storage::disk('s3');
            $client = $s3->getDriver()->getAdapter()->getClient();
            $expiry = "+10 minutes";
        
            $key = env('S3_PREFIX');
            switch ($type) {
            case "avatar":
                $key .= "avatar-" . $photo->path;
                break ;
            case "regular":
                $key .= "regular-" . $photo->path;
                break ;
            case "macro":
                $key .= $photo->path;
                break ;
            }
            $command = $client->getCommand('GetObject', [
                'Bucket' => env('S3_BUCKET'),
                'Key'    => $key,
            ]);
            $request = $client->createPresignedRequest($command, $expiry);
            $path = (string)$request->getUri();
        }
        return $path;
    }
    
    public static function uploadImage($user, $md5, $image) {
        /*
        ** Create new Photo
        */
        $path =  'images/' . time() . $user->id . '.jpg';
        $photo = new Photo();
        $photo->path = $path;
        $photo->origin_user_id = $user->id;
        $photo->md5 = $md5;

        /*
        ** Upload through storage -> AWS S3
        */
        $full = Image::make($image);
        $avatar = Image::make($image)->resize(80, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $regular = Image::make($image)->resize(155, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $full = $full->stream()->__toString();
        $avatar = $avatar->stream()->__toString();
        $regular = $regular->stream()->__toString();

        //Upload Photo
        Storage::disk('s3')->put(env('S3_PREFIX') . $path, $full, 'public');

        //Upload avatar
        Storage::disk('s3')->put(env('S3_PREFIX') . 'avatar-' . $path, $avatar, 'public');
        
        //Upload Regular size
        Storage::disk('s3')->put(env('S3_PREFIX') . 'regular-' . $path, $regular, 'public');
        
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
        $location->lat = $data['lat'];
        $location->lng = $data['lng'];
        $location->save();

        $channel = new Channel();
        $channel->save();

        /*
        ** Associate location to photo
        */
        $photo->location()->associate($location);
        $photo->channel()->associate($channel);
        $photo->save();

        /*
        ** Link user to photo
        */
        $user->photos()->attach($photo->id, [
            'admin' => true
        ]);

        $friends_id = [];
        $groups_id = [];
        if (array_key_exists('invites', $data)) {

            $invites = $data['invites'];
            
            if (array_key_exists('users', $invites)) {
                $friends_id = $invites['users'];
                foreach ($friends_id as $shared) {
                    $photo->users()->attach($shared, [
                        'admin' => false
                    ]);
                }
            }

            if (array_key_exists('groups', $invites)) {
                $groups_id = $invites['groups'];
                foreach ($groups_id as $group_id) {
                    $photo->groups()->attach($group_id);
                }
            }

        }

        
        /*
        ** Process Hashtags in description
        */
        InputFunctions::parse($photo, $photo->description);
        
        if ($photo->public) {
            $user->notify(new NewPublicPicture($user, $photo, 'slack'));
            foreach ($user->followers()->whereNotIn('users.id', $friends_id)->get() as $follower) {
                $follower->notify(new NewPublicPicture($user, $photo, 'database'));
            }
        } else {
            $user->notify(new NewPrivatePicture($user, $photo, 'slack'));    
        }

        /*
        ** Notify Users that a picture is in their album
        */
        if (!empty($friends_id)) {
            $friends = User::whereIn('users.id', $friends_id)->get();
            Notification::send($friends, new NewPrivatePicture($user, $photo, 'database'));
        }
        
        return (response(['photo_id' => $photo->id], 200));
    }

    public static function link($user, $photo_id, $invites) {
        $photo = Photo::find($photo_id);
        if (is_object($photo)) {
            $pivot = $user->photos()
                   ->where('photos.id', $photo_id)
                   ->where('admin', true)
                   ->first();
            if (is_object($pivot)) {

                if (array_key_exists('users', $invites)) {
                    $friends_id = $invites['users'];
                    foreach ($friends_id as $friend_id) {
                        $photo->users()->attach($friend_id, [
                            'admin' => false
                        ]);
                    }

                    if (!empty($friends_id)) {
                        $friends = User::whereIn('users.id', $friends_id)->get();
                        Notification::send($friends, new PrivatePicture($user, $photo, 'database'));
                    }
                }
                
                if (array_key_exists('groups', $invites)) {
                    $groups_id = $invites['groups'];
                    foreach ($groups_id as $group_id) {
                        $photo->groups()->attach($group_id);
                    }
                }

                return response(['status' => 'Success'], 200);
            }

            return response(['status' => 'Need to be admin'], 403);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }

    public static function unlink($user, $photo_id, $unlinks) {
        $photo = Photo::find($photo_id);
        if (is_object($photo)) {
            $pivot = $user->photos()
                   ->where('photos.id', $photo_id)
                   ->where('admin', true)
                   ->first();
            if (is_object($pivot)) {

                if (array_key_exists('users', $unlinks)) {
                    $friends_id = $unlinks['users'];
                    foreach ($friends_id as $friend_id) {

                        $currUser = $photo->users()
                                  ->where('users.id', $friend_id)
                                  ->first();

                        // Unlink only non-admin
                        if (!$currUser->pivot->admin) {
                            $photo->users()->detach($friend_id);
                        }
                        // TODO : Add Notification slack user unlinked from photo
                    }

                }
                
                if (array_key_exists('groups', $unlinks)) {
                    $groups_id = $unlinks['groups'];
                    foreach ($groups_id as $group_id) {
                        $photo->groups()->detach($group_id);
                    }

                    // TODO : Notif slack group unlinked
                }

                
                return response(['status' => 'Success'], 200);
            }

            return response(['status' => 'Need to be admin'], 403);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }
    
    public static function getInfos($user, $photo_id) {
        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response('Photo not found', 403);
        }

        $photo_path = PhotoFunctions::getUrl($photo, 'macro');

        /*
        ** Return Data with requested parameters
        */
        $originUser = User::find($photo->origin_user_id);
        $profile_pic_path = null;
        if (is_object($profile_pic = $originUser->profile_pic()->first())) {
            $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'macro');
        }

        $rated = PhotoRating::where('user_id', $user->id)
               ->where('photo_id', $photo->id)
               ->first();
        $ratingTypes = RatingType::all();
        $rating = [];
        foreach ($ratingTypes as $ratingType) {
            $count = PhotoRating::where('photo_id', $photo->id)
                   ->where('rating_type_id', $ratingType->id)
                   ->get()
                   ->count();
            $rating[$ratingType->name] = $count;
        }

        $following = $photo->users()
                   ->where('users.id', $user->id)
                   ->first();
        $data = [
            'id' => $photo->id,
            'url' => $photo_path,
            'description' => $photo->description,
            'admin_url' => $profile_pic_path,
            'admin_id' => $originUser->id,
            'admin_nickname' => $originUser->nickname,
            'public' => $photo->public,
            'mode' => $photo->mode,
            'rating' => $rating,
            'rated' => (is_object($rated) ? $rated->rating_type->name : null),
            'following' => is_object($following),
            'lat' => $photo->location->lat,
            'lng' => $photo->location->lng
        ];

        return response($data, 200);
    }

    public static function getRelatedContacts($user,
                                              $photo_id,
                                              $name_begin,
                                              $exclude) {
        $photo = Photo::find($photo_id);

        $groups = $photo->groups();
        $users = $photo->users();

        if ($name_begin) {
            $groups->where('name', 'like', $name_begin . '%');
            $users->where('nickname', 'like', $name_begin . '%');
        }

        $groups = $groups->get();
        $users = $users->get();
        
        if (is_object($photo)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $group) {
                $profile_pic_path = null;
                $profile_pic = $group->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
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
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
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

            return response($response, 200);
            
        }
        return response(['status' => 'Photo does not exists'], 403);
    }

    public static function edit($user, $data) {
        $photo = Photo::find($data['id']);
        if (is_object($photo)) {
            $pivot = $photo->users()
                   ->where('users.id', $user->id)
                   ->where('admin', true)
                   ->first();
            if (is_object($pivot)) {
                if (array_key_exists('description', $data)) {
                    $photo->description = $data['description'];
                }
                if (array_key_exists('public', $data)) {
                    $photo->public = $data['public'];
                }
                $photo->save();

                return response(['status' => 'Edit successfull'], 200);
            }

            return response(['status' => 'Access forbidden'], 403);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }

    public static function follow($user, $photo_id) {
        $photo = Photo::find($photo_id);
        if (is_object($photo)) {
            if ($photo->public) {
                $photo->users()->attach($user->id, [
                    'admin' => false
                ]);

                return response(['status' => 'Success'], 200);
            }

            return response(['status' => 'Photo is private'], 403);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }

    public static function unfollow($user, $photo_id) {
        $photo = Photo::find($photo_id);
        if (is_object($photo)) {
            
            $photo->users()->detach($user->id);
            return response(['status' => 'Success'], 200);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }

    /*
    ** Photo zoned
    */
    public static function unlockPicture($user, $photo_id, $userPos) {
        $photo = Photo::find($photo_id);
        if (is_object($photo) && $photo->mode == 'zoned') {
            $photoPos = [];
            $photoPos[0] = $photo->location->lat;
            $photoPos[1] = $photo->location->lng;

            /*
            ** Check if distance is inferior to MIN_UNLOCK_DISTANCE
            */
            $d = GeoTools::haversine($userPos, $photoPos);
            
            if ($d <= env(MIN_UNLOCK_DISTANCE)) {
            
                $photo->unlocks()->attach($user->id);
                return response(['status' => 'Photo unlocked'], 200);
            }

            return response(['status' => 'You need to be close '
                             . 'to the picture to unlock it. '
                             . 'Actual distance is ' . $d . ' meters'], 403);
        }

        return response(['status' => 'Photo does not exist or not zoned'], 403);
    }
}
