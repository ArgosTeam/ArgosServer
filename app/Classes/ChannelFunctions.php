<?php
namespace App\Classes;
use App\Models\Group;
use App\Models\User;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

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
        $channel->users()->attach($user->id, [
            'user_conv' => true
        ]);
        $channel->users()->attach($friend->id, [
            'user_conv' => true
        ]);
        return $channel;
    }

    public static function listAllUserChannels($user) {
        $response = new PriorityQueue();
        $response->setExtractFlags(PriorityQueue::EXTR_BOTH);
        foreach ($user->channels()->get() as $channel) {
            $channel_infos = [];
            if ($channel->event) {
                $event = $channel->event()->first();

                $channel_infos['type'] = 'event';
                $channel_infos['id'] = $event->id;
                $channel_infos['profile_pic'] = PhotoFunctions::getUrl($event->profile_pic()->first());
            } else if ($channel->group) {
                $group = $channel->group()->first();
                
                $channel_infos['type'] = 'group';
                $channel_infos['id'] = $group->id;
                $channel_infos['profile_pic'] = PhotoFunctions::getUrl($group->profile_pic()->first());
            } else if ($channel->photo) {
                $photo = $channel->photo()->first();
                
                $channel_infos['type'] = 'photo';
                $channel_infos['id'] = $photo->id;
                $channel_infos['profile_pic'] = PhotoFunctions::getUrl($photo);
            } else {
                $friend = $channel->users()
                        ->where('users.id', '!=', $user->id)
                        ->where('user_conv', true)
                        ->first();

                if (!is_object($friend)) {
                    continue;
                }
                
                $channel_infos['type'] = 'user';
                $channel_infos['id'] = $friend->id;
                $channel_infos['profile_pic'] = PhotoFunctions::getUrl($friend->profile_pic()->first());
            }

            $channel_infos['last_msg'] = null;
            $channel_infos['active'] = false;
            $weight = 0;
            $last_msg = $channel->messages()
                      ->orderBy('created_at', 'desc')
                      ->first();
            if (is_object($last_msg)) {
                $channel_infos['last_msg'] = $last_msg->content;
                $weight = $last_msg->id;
                $last_read = Message::find($channel->pivot->last_seen_message_id);

                if (!is_object($last_read) || $last_read->id != $last_msg->id) {
                    $channel_infos['active'] = true;
                }
            }

            // Using las_message_id as weight to sort by date, if no messages 0
            $response->insert($channel_infos, $weight);
        }

        return $response->toArray();
    }
}