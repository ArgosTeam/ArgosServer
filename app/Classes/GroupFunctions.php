<?php
/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 29/11/2016
 * Time: 7:55 AM
 */

namespace App\Classes;


use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Location;

class GroupFunctions
{


    public static function add($user, $request) {
        $group = Group::where('name', '=', $name)
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

            $location = Location::create(
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng');
            );

            $group->locations()->associate($location);
            $group->save();

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
        if (is_object($group)) {
            $user->groups()->attach($group_id, [
                'status' => 'pending',
                'admin' => false
            ]);
            return response('Join request sent', 200);
        }
        return response('Group does not exist', 404);
    }

    public static function accept($currentUser, $user_id, $group_id) {
        $group = Group::join('group_user', function ($join) {
            $join->on('groups.id', '=', 'group_user.group_id');
        })
               ->where('group_user.user_id', '=', $currentUser->id)
               ->find($group_id);
        $userToAccept = User::find($user_id);
        
        if ($group->admin) {
            $userToAccept->groups()->updateExistingPivot($group_id, [
                'status' => 'accepted',
                'admin' => false
            ]);
            return response('Join request sent', 200);
        } else {
            return response('Access refused, need to be admin', 404);
        }
    }
    
}