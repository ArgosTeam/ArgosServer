<?php
namespace App\Classes;


use App\Models\Group;
use App\Models\User;
use App\Models\Hashtag;
use Illuminate\Support\Facades\Log;
use App\Models\Location;
use App\Classes\PhotoFunctions;

class GroupFunctions
{
    public static function add($user, $request) {
        $group = Group::where('name', '=', $request->input('name'))
               ->first();
        if (is_object($group)) {
            return response('This group name already exists', 404);
        }
        
        if(is_object($user)) {

            $group = new Group();
            $group->name = $request->input('name');
            $group->public = $request->input('public');
            $group->description = $request->input('description');
            $group->address = $request->input('address');
            
            $location = new Location([
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng')
            ]);

            $location->save();
            $group->location()->associate($location);
            $group->save();
            
            /*
            ** Create hashtag if not exist
            ** Associate hashtag to group
            */
            foreach ($request->input('hashtags') as $name) {
                $hashtag = Hashtag::where('name', '=', $name)
                         ->first();
                if (!is_object($hashtag)) {
                    $hashtag = Hashtag::create([
                        'name' => $name
                    ]);
                }
                $hashtag->groups()->attach($group);
            }

            $user->groups()->attach($group->id, [
                'status' => 'accepted',
                'admin' => true
            ]);

        } else {
            return response('User not found', 404);
        }

        return response('Accepted', 200);
    }

    public static function join($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)
            && !$user->groups->contains($group_id)) {
            $user->groups()->attach($group_id, [
                'status' => 'pending',
                'admin' => false
            ]);
            return response('Join request sent', 200);
        }
        return response('Group does not exist or invite already exists', 404);
    }

    public static function accept($currentUser, $user_id, $group_id) {
        $group = Group::join('group_user', function ($join) {
            $join->on('groups.id', '=', 'group_user.group_id');
        })
               ->where('group_user.user_id', '=', $currentUser->id)
               ->find($group_id);
        $userToAccept = User::find($user_id);
        
        if (is_object($group) && $group->admin) {
            $userToAccept->groups()->updateExistingPivot($group_id, [
                'status' => 'accepted',
                'admin' => false
            ]);
            return response('Join request sent', 200);
        } else {
            return response('Access refused, need to be admin, or group does not exist', 404);
        }
    }

    public static function infos($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            $belong =$group->users()
                    ->where('users.id', '=', $user->id)
                    ->first();

            $data = [];
            $data['id'] = $group_id;
            $data['profile_pic'] = '';
            $data['name'] = $group->name;
            $data['hashtags'] = [];
            $hashtags = $group->hashtags()->get();
            foreach ($hashtags as $hashtag) {
                $data['hashtags'] = [
                    'id' => $hashtag->id,
                    'name' => $hashtag->name
                ];
            }
            $data['address'] = $group->address;
            $data['date'] = $group->created_at;
            if (is_object($belong)) {
                $data['belong'] = true;
                $data['admin'] = $belong->pivot->admin;
            } else {
                $data['belong'] = false;
                $data['admin'] = false;
            }
            
            return response($data, 200);
        }
        return response('Group does not exist', 404);
    }

    public static function profile_pic($user, $encode, $group_id) {
        $group = $user->groups()->where('groups.id', '=', $group_id)->first();
        if (!is_object($group)) {
            return response([ 'error' => 'access refused'], 404);
        }

        Log::info(print_r($group->pivot, true));
        if (!$group->pivot->admin) {
            return response(['error' => 'You need to be admin'], 404);
        }
        
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

        $group = Group::find($group_id);
        $group->profile_pic()->associate($photo->id);
        $group->save();

        return response(['photo_id' => $photo->id], 200);
    }
}