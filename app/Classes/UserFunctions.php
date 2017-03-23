<?php

namespace App\Classes;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Friend;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Classes\PhotoFunctions;

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
            $request = PhotoFunctions::getUrl($profile_pic, 'regular');
            $profile_pic_path = '' . $request->getUri() . '';
        }
        
        $response = [];
        $response['id'] = $userProfile->id;
        $response['nickname'] = '';
        $response['profile_pic'] = $profile_pic_path;
        $response['nickname'] = $userProfile->nickname;
        $response['firstname'] = '';
        $response['lastname'] = '';
        $response['university'] = '';
        $response['master'] = '';
        $response['stats'] = '';
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
        if (is_object(User::find($user_id))) {
            $user->followed()->attach($user_id);
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

    public static function getUserAlbum($user, $all) {
        $photos = $user->photos()
                ->where('admin', '=', true);
        if (!$all) {
            $photos->where('public', '=', true);
        }
        $photos = $photos->get();
        $response = [];
        foreach ($photos as $photo) {

            $request = PhotoFunctions::getUrl($photo, 'avatar');
            
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

    public static function getSession($user) {
        $profile_pic = $user->profile_pic()->first();
        $keys = ['avatar', 'regular'];
        $response = [
            'profile_pic_avatar' => null,
            'profile_pic_regular' => null,
            'nickname' => $user->nickname,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'user_id' => $user->id
        ];
        if (is_object($profile_pic)) {
            foreach ($keys as $key) {
                $request = PhotoFunctions::getUrl($profile_pic, $key);
                $response['profile_pic_' . $key] = '' . $request->getUri() . '';
            }
        }
        return response($response, 200);
    }

    public static function getRelatedContacts($user,
                                              $user_id,
                                              $name_begin,
                                              $exclude) {

        $currentUser = User::find($user_id);
        $groups = $currentUser->groups()
                ->where('status', 'accepted');
        $users = $currentUser->getFriends();

        if ($name_begin) {
            $groups->where('name', 'like', '%' . $name_begin);
            $users->where('nickname', 'like', '%' . $name_begin);
        }

        $groups = $groups->get();
        $users = $users->get();
        
        if (is_object($group)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $groupContact) {
                $profile_pic_path = null;
                $profile_pic = $groupContact->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $request = PhotoFunctions::getUrl($profile_pic);
                    $profile_pic_path = '' . $request->getUri() . '';
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
                    $request = PhotoFunctions::getUrl($profile_pic);
                    $profile_pic_path = '' . $request->getUri() . '';
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
}
