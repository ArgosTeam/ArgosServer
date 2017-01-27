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

class GroupFunctions
{


    public static function add($user, $public, $name) {

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

    public function fetch($groupId)
    {

        $group = Group::find($groupId);

        $rtn = [];
        if (is_object($group)) {

            $rtn["group"]["id"] = $group->id;
            $rtn["group"]["name"] = $group->name;

            foreach ($group->users AS $user){
                $rtn["group"]["users"][] = [
                    "id" => $user->id,
                    "firstName" => $user->firstName,
                    "lastName" => $user->lastName,
                ];
            }
        }

        return $rtn;

    }

    public function getUserGroups($userId){


        $user = User::find($userId);
        if(is_object($user)){

            $rtn = [];

            foreach($user->groups AS $group){
                $rtn["group"][] = [
                    "id" => $group->id,
                    "name" => $group->name,
                ];
            }


        }else{
            return response('User not found', 404);
        }

        return response('Accepted', 200);

    }

    public function inviteToGroup($groupId, $userId){

        $user = User::find($userId);
        $group = Group::find($groupId);
        if(is_object($user)){
            if(is_object($group)){
                $user->groups()->attach($groupId);
            }else{
                return response('Group not found', 404);
            }
        }else{
            return response('User not found', 404);
        }

        return response('Accepted', 200);

    }

    public function accept($groupId, $userId){

        $user = User::find($userId);
        $group = Group::find($groupId);
        if(is_object($user)){
            if(is_object($group)){
                $user->groups()->updateExistingPivot($groupId);
            }else{
                return response('Group not found', 404);
            }
        }else{
            return response('User not found', 404);
        }

        return response('Accepted', 200);

    }

    public function decline($groupId, $userId){

        $user = User::find($userId);
        $group = Group::find($groupId);
        if(is_object($user)){
            if(is_object($group)){
                $user->groups()->updateExistingPivot($groupId);
            }else{
                return response('Group not found', 404);
            }
        }else{
            return response('User not found', 404);
        }

        return response('Accepted', 200);

    }
}