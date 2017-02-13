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
        return $user->getFriends()
            ->where('firstName', 'like', $nameBegin . '%')
            ->where('lastName', 'like', $nameBegin . '%')
            ->get();
    }

    private static function getUnknownUsers($user, $nameBegin, $limit) {
        $ids = $user->getFriends()->get()->pluck('id');
        return User::whereNotIn('id', $ids)
            ->where('firstName', 'like', $nameBegin . '%')
            ->where('lastName', 'like', $nameBegin . '%')
            ->limit(15)
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
            if (is_object($user->pivot)) {
                $newEntry['own'] = $user->pivot->own;
                $newEntry['friend'] = $user->pivot->active;
                $newEntry['pending'] = $newEntry['friend'] ? false : true;
            } else {
                $newEntry['own'] = false;
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


    public static function photos($user, $nameBegin) {
        $user_groups = $user->groups()
                     ->where('name', 'like', $nameBegin . '%')
                     ->get();
        // TODO : handling of group_photo

        $friends = $user->getFriends()
                 ->where('firstName', 'like', $nameBegin . '%')
                 ->orWhere('lastName', 'like', $nameBegin . '%')
                 ->get();

        $hashtags = Hashtag::where('name', 'like', $nameBegin . '%')
                  ->get();

        $friends_photos = $user->photos()
                        ->whereIn('origin_user_id',
                                  $friends->pluck('id'))
                        ->get();
        $hashtags_photos = $hashtags->photos()
                         ->whereHas('users', function ($query) use ($user) {
                             $query->where('users.id', '=', $user->id);
                         })->get();
        $photos = $friends_photos->merge($hashtags_photos);
        
        $response = [];
        foreach ($photos as $photo) {
            $location = $photo->location()->first();
            
            // Get signed url from s3
            $s3 = Storage::disk('s3');
            $client = $s3->getDriver()->getAdapter()->getClient();
            $expiry = "+10 minutes";
            
            $command = $client->getCommand('GetObject', [
                'Bucket' => env('S3_BUCKET'),
                'Key'    => "avatar-" . $photo->path,
            ]);
            $request = $client->createPresignedRequest($command, $expiry);

            
            $response[] = [
                'id' => $photo->id,
                'url' => '' . $request->getUri() . '',
                'name' => $photo->name,
                'description' => $photo->description,
                'lat' => $location->lat,
                'lng' => $location->lng
            ];
        }

        return response($response, 200);
    }
}