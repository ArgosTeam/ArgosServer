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
        return User::select(['users.*', 'user_users.friend_id', 'user_users.active', 'user_users.own'])
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
        return User::select(['users.*', 'user_users.friend_id', 'user_users.active', 'user_users.own'])
            ->leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
            ->whereNotIn('users.id', $ids)
            ->where('firstName', 'like', $nameBegin . '%')
            ->orWhere('lastName', 'like', $nameBegin . '%')
            ->whereNotIn('users.id', $ids)
            ->limit($limit)
            ->get();
    }
    
    private static function getUsers($user, $nameBegin, $knownOnly) {
        $users = SearchFunctions::getknownUsers($user, $nameBegin);
        if (!$knownOnly && ($limit = 15 - $users->count()) > 0) {
            $users = $users->merge(SearchFunctions::getUnknownUsers($user, $nameBegin, $limit));
        }
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
            $newEntry['id'] = $user->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $user->firstName . ' ' . $user->lastName;
            $newEntry['type'] = 'user';
            $newEntry['own'] = $user->own;
            if ($user->active !== null) {
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
        $data = [];
        
        foreach ($events as $event) {
            $newEntry = [];
            $newEntry['id'] = $event->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $event->name;
            if (is_object($event->pivot)) {
                $newEntry['accepted'] = ($event->pivot->status == "accepted" ? true : false);
                $newEntry['pending'] = ($event->pivot->status == "pending" ? true : false);
            } else {
                $newEntry['accepted'] = false;
                $newEntry['pending'] = false;
            }
            $data[] = $newEntry;
        }
        Log::info('EVENTS : ' . print_r($data,true));
        return response($data, 200);
    }

    
}