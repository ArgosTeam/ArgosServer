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

    private static function getKnownUsers($user, $nameBegin) {
        return User::leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
            ->where('users.firstName', 'like', $nameBegin . '%')
            ->where('user_users.friend_id', '=', $user->id)
            ->orWhere('users.lastName', 'like', $nameBegin . '%')
            ->where('user_users.friend_id', '=', $user->id)
            ->limit(30)
            ->get();
    }

    private static function getUnknownUsers($user, $nameBegin, $limit) {
        return User::leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
            ->where('users.firstName', 'like', $nameBegin . '%')
            ->where('user_users.friend_id', '=', null)
            ->orWhere('users.lastName', 'like', $nameBegin . '%')
            ->where('user_users.friend_id', '=', null)
            ->limit($limit)
            ->get();
    }
    
    private static function getUsers($user, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getknownUsers($user, $nameBegin);
        if (!$knowOnly && ($limit = 30 - $known_users->count()) > 0) {
            $users = array_merge($users, SearchFunctions::getUnknownUsers($user, $namebegin, $limit));
        }
        return $users;
    }
    
    public static function  getContacts($currentUser, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getUsers($currentUser, $nameBegin, $knownOnly);
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
                $newEntry['friend'] = $user->active;
                $newEntry['pending'] = $user->active == null ? false : true;
            } else {
                $newEntry['friend'] = false;
                $newEntry['pending'] = false;
            }
            $data[] = $newEntry;
        }
        foreach ($groups as $group) {
            $newEntry = [];
            $newEntry['id'] = $group->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $group->name;
            $newEntry['public'] = $group->public;
            $newEntry['type'] = 'group';
            $newEntry['pending'] = false;
            $data[] = $newEntry;
        }
        Log::info('DATAAAAA : ' . print_r($data, true));
        return response($data, 200);
    }
}