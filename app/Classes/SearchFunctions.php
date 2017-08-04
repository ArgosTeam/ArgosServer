<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Event;
use App\Models\Friend;
use App\Models\Hashtag;
use Illuminate\Support\Facades\Input;
use App\Classes\PhotoFunctions;

class SearchFunctions {
    
    public static function  events($currentUser, $nameBegin, $knownOnly) {
        $events = SearchFunctions::getEvents($currentUser, $nameBegin, 16, $knownOnly, []);
        $data = [];
        
        foreach ($events as $event) {
            $profile_pic = $event->profile_pic()->first();
            $profile_pic_path = null;
            if (is_object($profile_pic)) {
                $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'avatar');
            }
            
            $newEntry = [];
            $newEntry['event_id'] = $event->id;
            $newEntry['profile_pic'] = $profile_pic_path;
            $newEntry['event_name'] = $event->name;
            if (is_object($event->pivot)) {
                $newEntry['accepted'] = ($event->pivot->status == "accepted" ? true : false);
                $newEntry['pending'] = ($event->pivot->status == "pending" ? true : false);
            } else {
                $newEntry['accepted'] = false;
                $newEntry['pending'] = false;
            }
            $data[] = $newEntry;
        }
        return response(["content" => $data], 200);
    }


    public static function photos($user, $nameBegin) {
        $user_groups = $user->groups()
                     ->where('name', 'like', $nameBegin . '%')
                     ->get();

        $friends = $user->getFriends()
                 ->where('nickname', 'like', $nameBegin . '%')
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

            $response[] = [
                'id' => $photo->id,
                'url' => PhotoFunctions::getUrl($photo, 'regular'),
                'name' => $photo->name,
                'description' => $photo->description,
                'lat' => $location->lat,
                'lng' => $location->lng
            ];
        }

        return response(["content" => $response], 200);
    }

    private static function getKnownUsers($user, $nameBegin, $limit, $self = false, $exclude_ids = []) {
        $query = $user->getFriends()
               ->where(function ($query) use ($nameBegin) {
                   $query->where('nickname', 'like', $nameBegin . '%');
               });
        if (!empty($exclude_ids)) {
            $query->whereNotIn('users.id', $exclude_ids);
        }
        $query->where('users.id', '!=', $user->id)
            ->limit($limit);
        return $query->get();
    }
    
    private static function getUnknownUsers($user, $nameBegin, $limit, $self = false, $exclude_ids = []) {
        $ids = $user->getFriends()->get()->pluck('id');
        $ids[] = $user->id;
        $query = User::where(function ($query) use ($nameBegin) {
            $query->where('nickname', 'like', $nameBegin . '%');
        })
               ->limit(15);
        $merge_ids = array_merge($ids->all(), $exclude_ids);
        if (!empty($merge_ids)) {
            $query->whereNotIn('users.id', $merge_ids);
        }
        return $query->get();
    }

    private static function getUsers($user,
                                     $nameBegin,
                                     $count,
                                     $knownOnly = false,
                                     $self = false,
                                     $exclude_ids = []) {
        $users = SearchFunctions::getknownUsers($user, $nameBegin, $count, $self, $exclude_ids);
        if (!$knownOnly && ($limit = $count - $users->count()) > 0) {
            $users = $users->merge(SearchFunctions::getUnknownUsers($user,
                                                                    $nameBegin,
                                                                    $limit,
                                                                    $self,
                                                                    $exclude_ids));
        }
        return $users;
    }

    private static function getKnownGroups($user,
                                           $nameBegin,
                                           $count,
                                           $exclude) {
        $query = $user->groups()
               ->where('status', 'accepted');
        if ($nameBegin) {
            $query->where('groups.name', 'like', '%' . $nameBegin);
        }
        if (!empty($exclude)) {
            $query->whereNotIn('groups.id', $exclude);
        }
        $query->limit($count);
        return $query->get();
    }

    private static function getUnknownGroups($user,
                                             $nameBegin,
                                             $count,
                                             $exclude) {
        $ids = $user->groups()
             ->where('status', 'accepted')
             ->get()
             ->pluck('id');
        $merge_ids = array_merge($ids->all(), $exclude);
        $query = $user->groups()
               ->where('public', true)
               ->whereNotIn('groups.id', $merge_ids);
        if ($nameBegin) {
            $query->where('groups.name', 'like', '%' . $nameBegin);   
        }
        $query->limit($count);
        return $query->get();
    }
    
    public static function getGroups($user,
                                     $nameBegin,
                                     $count,
                                     $knownOnly = false,
                                     $exclude) {
        $groups = SearchFunctions::getknownGroups($user, $nameBegin, $count, $exclude);
        $count -= $groups->count();
        if (!$knownOnly && $count > 0) {
            $groups = $groups->merge(SearchFunctions::getUnknownGroups($user,
                                                                       $nameBegin,
                                                                       $count,
                                                                       $exclude));
        }
        return $groups;
    }

    private static function getKnownEvents($user,
                                           $nameBegin,
                                           $count,
                                           $exclude) {
        $query = $user->events()
               ->where('status', 'accepted');
        if ($nameBegin) {
            $query->where('events.name', 'like', '%' . $nameBegin);
        }
        if (!empty($exclude)) {
            $query->whereNotIn('events.id', $exclude);
        }
        $query->limit($count);
        return $query->get();
    }

    private static function getUnknownEvents($user,
                                             $nameBegin,
                                             $count,
                                             $exclude) {
        $ids = $user->events()
             ->where('status', 'accepted')
             ->get()
             ->pluck('id');
        $merge_ids = array_merge($ids->all(), $exclude);
        $query = $user->events()
               ->where('public', true)
               ->whereNotIn('events.id', $merge_ids);
        if ($nameBegin) {
            $query->where('events.name', 'like', '%' . $nameBegin);   
        }
        $query->limit($count);
        return $query->get();
    }
    
    public static function getEvents($user,
                                     $nameBegin,
                                     $count,
                                     $knownOnly = false,
                                     $exclude) {
        $events = SearchFunctions::getknownEvents($user, $nameBegin, $count, $exclude);
        $count -= $events->count();
        if (!$knownOnly && $count > 0) {
            $events = $events->merge(SearchFunctions::getUnknownEvents($user,
                                                                       $nameBegin,
                                                                       $count,
                                                                       $exclude));
        }
        return $events;
    }
    
    public static function globalSearch($user, $data) {
        $name_begin = $data['name_begin'];
        $mode = $data['mode'];
        $exclude = array_key_exists('exclude', $data)
                 ? $data['exclude'] : [];

        if (!array_key_exists('users', $exclude)) {
            $exclude['users'] = [];
        }
        if (!array_key_exists('groups', $exclude)) {
            $exclude['groups'] = [];
        }
        if (!array_key_exists('events', $exclude)) {
            $exclude['events'] = [];
        }

        $count = env('GLOBAL_SEARCH_COUNT');

        $response = [];
        if ($mode == 'users') {
            $users = SearchFunctions::getUsers($user,
                                               $name_begin,
                                               $count,
                                               false,
                                               true,
                                               $exclude['users']);
            foreach ($users as $item) {
                $profile_pic_path = null;
                $profile_pic = $item->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }

                $pivotFriend = $user->friends()
                             ->where('friend_id', $item->id)
                             ->first();
                
                $response[] = [
                    'id' => $item->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $item->nickname,
                    'pending' => is_object($pivotFriend) && !$pivotFriend->pivot->active,
                    'friend' => is_object($pivotFriend) && $pivotFriend->pivot->active
                ];
            }
        }

        if ($mode == 'groups') {
            $groups = SearchFunctions::getGroups($user,
                                                 $name_begin,
                                                 $count,
                                                 false,
                                                 $exclude['groups']);

            foreach ($groups as $item) {
                $profile_pic_path = null;
                $profile_pic = $item->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $response[] = [
                    'id' => $item->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $item->name
                ];
            }
        }

        if ($mode == 'events') {
            $events = SearchFunctions::getEvents($user,
                                                 $name_begin,
                                                 $count,
                                                 false,
                                                 $exclude['events']);

            foreach ($events as $item) {
                $profile_pic_path = null;
                $profile_pic = $item->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $response[] = [
                    'id' => $item->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $item->name
                ];
            }
        }

        if ($mode == "hashtags") {
            $hashtags = Hashtag::where('name', 'like', $name_begin . '%')
                      ->limit($count)
                      ->get();

            foreach ($hashtags as $hashtag) {
                // Return hashtags link
                $results = [];
                $photos = $hashtag->photos()->get();
                if (!empty($photos)) {
                    foreach ($photos as $photo) {
                        $url = PhotoFunctions::getUrl($photo);
                        $results['photos'][] = [
                            'id' => $photo->id,
                            'lat' => $photo->location->lat,
                            'lng' => $photo->location->lng,
                            'url' => $url
                        ];
                    }
                }
                $events = $hashtag->events()->get();
                if (!empty($events)) {
                    foreach ($events as $event) {
                        $profile_pic_path = null;
                        $profile_pic = $event->profile_pic()->first();
                        if (is_object($profile_pic)) {
                            $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                        }
                        
                        $results['events'][] = [
                            'id' => $event->id,
                            'lat' => $event->location->lat,
                            'lng' => $event->location->lng,
                            'profile_pic' => $profile_pic_path
                        ];
                    }
                }
            }
        }

        return response(["content" => $results], 200);
    }
}