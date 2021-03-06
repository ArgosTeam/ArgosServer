<?php
namespace App\Classes;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Friend;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Classes\PhotoFunctions;
use App\Notifications\Follow;
use App\Notifications\Unfollow;
use App\Models\RatingType;
use App\Classes\ChannelFunctions;

class UserFunctions
{

    public static function getInfos($user, $id) {
        $idToSearch = ($id == -1 ? $user->id : $id);

        $userProfile = User::find($idToSearch);
        $friendShip = Friend::where('user_id', '=', $user->id)
                    ->where('friend_id', '=', $userProfile->id)
                    ->first();
        $profile_pic = $userProfile->profile_pic()->first();
        $profile_pic_path = null;

        if (is_object($profile_pic)) {
            $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'regular');
        }

        $groups = GroupFunctions::getGroupsOnUserProfile($userProfile, $user, null);
        $photos = UserFunctions::getUserAlbum($userProfile, $user);
        $events = EventFunctions::getEventsOnProfile($userProfile, $user, null);
        
        $response = [];
        $response['id'] = $userProfile->id;
        $response['nickname'] = '';
        $response['profile_pic_regular'] = $profile_pic_path;
        $response['nickname'] = $userProfile->nickname;
        $response['university'] = '';
        $response['master'] = '';
        $response['stats'] = '';
        $response['firstname'] = null;
        $response['lastname'] = null;
        $response['cursus'] = $userProfile->cursus;
        $response['followers'] = $userProfile->followers()->get()->count();
        $response['following'] = $userProfile->followed()->get()->count();
        $response['events_count'] = $events->count();
        $response['groups_count'] = $groups->count();
        $response['friends_count'] = $userProfile->getFriends()->count();
        $response['photos_count'] = count($photos);
        if ($id == -1) {
            $response['messages_count'] = 0;
        } else {
            $channel = ChannelFunctions::getUserChannel($user, $userProfile);
            $response['messages_count'] = $channel->messages()->count();
        }

        $followPivot = $userProfile->followers()
                     ->where('users.id', $user->id)
                     ->first();

        $followedPivot = $user->followers()
                       ->where('users.id', $userProfile->id)
                       ->first();
        
        $response['follow'] = is_object($followPivot);
        $response['followed'] = is_object($followedPivot);
        if (is_object($friendShip)) {
            $response['friend'] = $friendShip->active;
            $response['pending'] = !$friendShip->active;
            $response['own'] = $friendShip->own;
            if ($friendShip->active) {
                $response['firstname'] = $userProfile->firstname;
                $response['lastname'] = $userProfile->lastname;
            }
        } else {
            $response['friend'] = false;
            $response['pending'] = false;
            $response['own'] = false;
        }
        
        return response($response, 200);
    }

    public static function follow($user, $user_id) {
        $followed = User::find($user_id);
        if (is_object($followed)) {
            if ($user->followed->contains($user_id)) {
                return response(['status' => 'User already followed'], 403);
            }
            $user->followed()->attach($user_id);
            $user->notify(new Follow($followed, 'slack'));
            $followed->notify(new Follow($user, 'database'));
            return response(['status' => 'Success'], 200);
        } else {
            return response(['status' => 'User does not exist'], 403);
        }
    }

    public static function unfollow($user, $user_id) {
        $followed = User::find($user_id);
        if (is_object($followed)) {
            if (!$user->followed->contains($user_id)) {
                return response(['status' => 'User is not followed'], 403);
            }
            $user->followed()->detach($user_id);
            $user->notify(new Unfollow($followed, 'slack'));
            return response(['status' => 'Success'], 200);
        } else {
            return response(['status' => 'User does not exist'], 403);
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
            return response(['refused' => 'Photo already exists'], 403);
        }

        $photo = PhotoFunctions::uploadImage($user, $md5, $decode);
        $photo->save();

        $user->profile_pic()->associate($photo);
        $user->save();

        return response(['photo_id' => $photo->id], 200);
    }

    public static function getUserAlbum($userProfile, $user) {

        // Get private photos that user is allow to see on userProfile
        $private_pictures = $userProfile->photos()
                          ->where('public', false)
                          ->whereHas('users', function ($query) use ($user) {
                              $query->where('users.id', $user->id);
                          })
                          ->get();

        // Get public pictures
        $public_pictures = $userProfile->photos()
                         ->where('public', true)
                         ->get();
        
        $photos = $private_pictures->merge($public_pictures);
        
        $response = [];
        foreach ($photos as $photo) {
            $response[] = [
                'id' => $photo->id,
                'lat' => $photo->location->lat,
                'lng' => $photo->location->lng,
                'description' => $photo->description,
                'path' => PhotoFunctions::getUrl($photo, 'regular'),
                'public' => $photo->public,
                'mode' => $photo->mode,
                'admin' => $photo->pivot->admin
            ];
        }
        return $response;
    }

    public static function getSession($user) {
        $profile_pic = $user->profile_pic()->first();
        $keys = ['avatar', 'regular'];

        /*
        ** Getting assets from s3 directory assets
        */
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+7 days";
        
        $response = [
            'profile_pic_avatar' => null,
            'profile_pic_regular' => null,
            'nickname' => $user->nickname,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'dob' => $user->dob,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_id' => $user->id,
            'cursus' => $user->cursus
        ];


        /*
        ** Getting profile_pics as specified in keys
        */
        if (is_object($profile_pic)) {
            foreach ($keys as $key) {
                $response['profile_pic_' . $key] = PhotoFunctions::getUrl($profile_pic, $key);
            }
        }
        return response($response, 200);
    }
    
    public static function getRelatedContacts($user,
                                              $user_id,
                                              $name_begin,
                                              $exclude) {
        
        $currentUser = ($user_id == -1 ? $user : User::find($user_id));
        $groups = GroupFunctions::getGroupsOnUserProfile($currentUser, $user, $name_begin);
        $users = $currentUser->getFriends();

        if ($name_begin) {
            $users->where('nickname', 'like', $name_begin . '%');
        }

        $users = $users->get();
        
        if (is_object($currentUser)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $groupContact) {
                $profile_pic_path = null;
                $profile_pic = $groupContact->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $response['groups'][] = [
                    'id' => $groupContact->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $groupContact->name,
                    'is_contact' => ($groupContact->users->contains($user->id)
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
                if ($contact->getFriends->contains($user->id)) {
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
        return response(['status' => 'Group does not exists'], 403);
    }

    public static function events($userProfile,
                                  $user,
                                  $name_begin,
                                  $exclude) {
        if (is_object($userProfile)) {
            $response = [];
            $events = EventFunctions::getEventsOnProfile($userProfile, $user, $name_begin);
            
            foreach ($events as $event) {

                $profile_pic_path = null;
                $profile_pic = $event->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                
                $response[] = [
                    'event_id' => $event->id,
                    'profile_pic' => $profile_pic_path,
                    'event_name' => $event->name,
                    'invited' => ($event->pivot->status == 'invited'
                                  ? true : false),
                    'accepted' => ($event->pivot->status == 'accepted'
                                   ? true : false),
                    'date' => $event->start
                ];
            }

            return response(['content' => $response], 200);
        }
        
        return response(['status' => 'User does not exist'], 403);
    }

    public static function edit($user, $data) {
        if (array_key_exists('firstname', $data)) {
            $user->firstname = $data['firstname'];
        }
        if (array_key_exists('lastname', $data)) {
            $user->lastname = $data['lastname'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        if (array_key_exists('sex', $data)) {
            $user->sex = $data['sex'];
        }
        if (array_key_exists('dob', $data)) {
            $user->dob = $data['dob'];
        }
        if (array_key_exists('cursus', $data)) {
            $user->cursus = $data['cursus'];
        }
        $user->save();
        
        if (array_key_exists('profile_pic', $data)) {
            UserFunctions::profile_pic($user, $data['profile_pic']);
        }
        return response(['status' => 'Success'], 200);
    }
}
