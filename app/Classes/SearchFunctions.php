<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Event;
use App\Models\Friend;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class SearchFunctions {


    /*
    ** Search Users
    */
    private static function getKnownUsers($user, $nameBegin) {
        $ids = is_object($user->friends) ? $user->friends->pluck('friend_id') : [];
        return User::select(['users.*'])
            ->leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
            ->whereIn('users.id', $ids)
            ->where('firstName', 'like', $nameBegin . '%')
            ->orWhere('lastName', 'like', $nameBegin . '%')
            ->whereIn('id', $ids)
            ->limit(15)
            ->get();
    }

    private static function getUnknownUsers($user, $nameBegin, $limit) {
        $ids = is_object($user->friends) ? $user->friends->pluck('friend_id') : [];
        return User::select(['users.*'])
            leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
            ->whereNotIn('users.id', $ids)
            ->where('firstName', 'like', $nameBegin . '%')
            ->orWhere('lastName', 'like', $nameBegin . '%')
            ->whereNotIn('id', $ids)
            ->limit($limit)
            ->get();
    }
    
    private static function getUsers($user, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getknownUsers($user, $nameBegin);
        if (!$knownOnly && ($limit = 15 - $users->count()) > 0) {
            $users = $users->merge(SearchFunctions::getUnknownUsers($user, $nameBegin, $limit));
        }
        Log::info(print_r($users, true));
        return $users;
    }
    
    public static function  getContacts($currentUser, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getUsers($currentUser, $nameBegin, $knownOnly);
        $groups =  Group::where('name', 'like', $nameBegin . '%')
                ->limit(15)
                ->get();
        $data = [];
        foreach ($users as $user) {
            $newEntry = [];
            $newEntry['id'] = $user->user_id;
            $newEntry['url'] = null;
            $newEntry['name'] = $user->firstName . ' ' . $user->lastName;
            $newEntry['type'] = 'user';
            if ($user->active != null) {
                $newEntry['friend'] = $user->active;
                $newEntry['pending'] = $newEntry['friend'] ? false : true;
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
        Log::info(print_r($data, true));
        return response($data, 200);
    }

    /*
    ** Search Events
    */
    
    private static function getKnownEvents($user, $nameBegin) {
        return $user->events()
            ->where('events.name', 'like', $nameBegin . '%')
            ->limit(30)
            ->get();
    }

    private static function getUnknownEvents($user, $nameBegin, $limit) {
        return Event::where('id', '!=', $user->events->pluck('id'))
            ->get();
    }
    
    private static function getEvents($user, $nameBegin, $knownOnly) {
        $events = SearchFunctions::getknownEvents($user, $nameBegin);
        if (!$knownOnly && ($limit = 30 - $events->count()) > 0) {
            $events = $events->merge(SearchFunctions::getUnknownEvents($user, $nameBegin, $limit));
        }
        return $events;
    }
    
    public static function  events($currentUser, $nameBegin, $knownOnly) {
        $events = SearchFunctions::getEvents($currentUser, $nameBegin, $knownOnly);
        //Log::info('EVENTS : ' . print_r($events, true));
        $data = [];
        // foreach ($events as $user) {
        //     $newEntry = [];
        //     $newEntry['id'] = $user->id;
        //     $newEntry['url'] = null;
        //     $newEntry['name'] = $user->firstName . ' ' . $user->lastName;
        //     $newEntry['type'] = 'user';
        //     if ($currentUser->id == $user->friend_id) {
        //         $newEntry['friend'] = $user->active;
        //         $newEntry['pending'] = $user->active == null ? false : true;
        //     } else {
        //         $newEntry['friend'] = false;
        //         $newEntry['pending'] = false;
        //     }
        //     $data[] = $newEntry;
        // }
        // foreach ($groups as $group) {
        //     $newEntry = [];
        //     $newEntry['id'] = $group->id;
        //     $newEntry['url'] = null;
        //     $newEntry['name'] = $group->name;
        //     $newEntry['public'] = $group->public;
        //     $newEntry['type'] = 'group';
        //     $newEntry['pending'] = false;
        //     $data[] = $newEntry;
        // }
        return response($data, 200);
    }

    
}