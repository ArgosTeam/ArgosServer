<?php

namespace App\Classes;


use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\FriendRequest;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestRejected;

class FriendFunctions
{
    public static function add($user, $friend, $own = false, $active = false) {
        if ($user->friends->contains($friend->id)) {
            return response('Friendship already exists', 403);
        }
        $user->friends()->attach($friend->id, [
            'own' => $own,
            'active' =>$active
        ]);
        if ($own) {
            $user->notify(new FriendRequest($user, $friend, 'slack'));
        } else {
            $user->notify(new FriendRequest($user, $friend, 'database'));
        }
        $user->followed()->attach($friend->id);
        return response(['status' => 'success'], 200);
    }

    public static function accept($user, $friend, $own = false) {
        $user->friends()->updateExistingPivot($friend->id, [
            'active' => true
        ]);
        if ($own) {
            $user->notify(new FriendRequestAccepted($user, $friend, 'database'));
        } else {
            $user->notify(new FriendRequestAccepted($user, $friend, 'slack'));
        }
        return response(['status' => 'success'], 200);
    }

    public static function refuse($user, $friend, $own = false) {
        $pivot = $user->friends()->where('users.id', $friend->id)->first();
        if (is_object($pivot) && !$pivot->pivot->active) {
            $user->friends()->detach($friend->id);

            if ($own) {
                $user->notify(new FriendRequestRejected($user, $friend, 'database'));
            } else {
                $user->notify(new FriendRequestRejected($user, $friend, 'slack'));    
            }
        
            return response(['status' => 'success'], 200);
        }
        
        return response(['status' => 'Access denied'], 403);
    }

    public static function cancel($user, $friend) {
        $user->friends()->detach($friend->id);
        return response(['status' => 'success'], 200);
    }

    public static function getFavorites($user) {
        $friends = $user->getFriends();

        // Impact of algorithm only applies on photos where user is admin
        $self_album_ids = $user->photos()
                    ->where('origin_user_id', $user->id)
                    ->get()
                    ->pluck('id');

        // Weight Heuristic to get favorites contacts
        $shared_weight = 5;
        $ranking_weight = 1;

        $results = new PriorityQueue();
        $results->setExtractFlags(PriorityQueue::EXTR_BOTH);
        foreach ($friends as $friend) {
            $shared_count = $friend->photos()
                          ->where('origin_user_id', $user->id)
                          ->count();
            $ranking_count = $friend->photoRatings()
                           ->whereIn('photo_id', $self_album_ids)
                           ->count();

            $total_weight = $shared_weight * $shared_count + $ranking_weight * $ranking_count;
            $results->insert($friend, $total_weight);
        }

        return response(["content" => $results->toArray(env('FRIENDS_FAVORITES_COUNT'))]);
    }
}
