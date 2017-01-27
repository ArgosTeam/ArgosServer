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

class GroupFunctions
{


    public static function add($user, $public, $name) {
        $group = Group::where('name', '=', $name)
               ->first();
        if (is_object($group)) {
            return response('This group name already exists', 404);
        }
        
        if(is_object($user)) {

            $group = new Group();
            $group->name = $name;
            $group->public = $public;
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
        $user->groups()->attach($group_id, [
            'status' => 'pending',
            'admin' => false
        ]);
        return response('Join request sent', 200);
    }

    public static function accept($currentUser, $user_id, $group_id) {
        $group = Group::find($group_id);
        $userToAccept = User::find($user_id);

        Log::info(print_r($userToAccept, true));
        // if ($group->admin) {
        //     $user->groups()->attach($group_id, [
        //         'status' => 'accepted',
        //         'admin' => false
        //     ]);
        //     return response('Join request sent', 200);
        // } else {
        //     return response('Access refused, need to be admin', 404);
        // }
    }
    
}