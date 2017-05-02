<?php
namespace App\Classes;
use App\Models\Group;
use App\Models\User;
use App\Models\Hashtag;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Channel;
use App\Classes\PhotoFunctions;

class ChannelFunctions
{
    public static function getUserChannel($user, $friend) {
        $channel = $user->channels()
                 ->whereHas('users', function ($query) use ($friend) {
                     $query->where('users.id', $friend->id);
                 })
                 ->first();
        if (is_object($channel)) {
            return $channel;
        }
        return ChannelFunctions::createUserChannel($user, $friend);
    }

    private static function createUserChannel($user, $friend) {
        $channel = new Channel();

        $channel->save();
        $channel->users()->attach($user->id);
        $channel->users()->attach($friend->id);
        return $channel;
    }

    
}