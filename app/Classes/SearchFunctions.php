<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class SearchFunctions {

    private static function getUsers($user, $nameBegin, $knownOnly) {
        $users = [];
        if (!$knownOnly) {
            $users = User::leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
                   ->where('users.firstname', 'like', $nameBegin . '%')
                   ->orWhere('users.lastname', 'like', $nameBegin . '%')
                   ->limit(13)
                   ->get();
        } else {
            $users = User::leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
                   ->where('users.firstname', 'like', $nameBegin . '%')
                   ->where('user_users.friend_id', '=', $user->id)
                   ->orWhere('users.lastname', 'like', $nameBegin . '%')
                   ->where('user_users.friend_id', '=', $user->id)
                   ->limit(13)
                   ->get();
        }
        return $users ? $users : [];
    }
    
    public static function  getContacts($currentUser, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getUsers($currentUser, $nameBegin, $knownOnly);
        Log::info(print_r($users, true));
        $groups =  Group::where('name', 'like', $nameBegin . '%')
                ->limit(12)
                ->get();
        $data = [];
        foreach ($users as $user) {
            $newEntry = [];
            $newEntry['id'] = $user->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $user->firstName . ' ' . $user->lastName;
            $newEntry['type'] = 'user';
            if ($currentUser->id == $user->friend_id) {
                $newEntry['pending'] = $user->active == null ? false : $user->active;
            } else {
                $newEntry['pending'] = false;
            }
            $data[] = $newEntry;
        }
        foreach ($groups as $group) {
            $newEntry = [];
            $newEntry['id'] = $group->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $group->name;
            $newEntry['type'] = 'group';
            $newEntry['pending'] = false;
            $data[] = $newEntry;
        }
        return (json_encode($data));
    }
}