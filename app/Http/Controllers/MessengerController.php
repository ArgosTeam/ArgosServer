<?php

namespace App\Http\Controllers;

use App\Classes\EventFunctions;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\User;
use App\Models\Message;
use App\Models\Channel;
use App\Models\Group;
use App\Classes\ChannelFunctions;
use App\Classes\MessageFunctions;

class MessengerController extends Controller
{
    public function sendToUser(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input('id');
        $content = $request->input('content');

        $friend = User::find($friend_id);
        /*
        ** Check if user exists
        */
        if (is_object($friend)) {

            /*
            ** Check if user is friend
            */
            if ($user->getFriends->contains($friend->id)) {

                $channel = ChannelFunctions::getUserChannel($user, $friend);
                return MessageFunctions::sendMessageInChannel($user, $content, $channel);
            }

            return response(['status' => 'You need to be friend'], 403);
        }

        return response(['status' => 'User does not exist'], 403);
    }

    public function sendInGroup(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        $content = $request->input('content');
        $group = Group::find($group_id);

        if (is_object($group)) {
            $pivot = $group->users()
                   ->where('status', 'accepted')
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($pivot)) {
                $channel = $group->channel;
                return MessageFunctions::sendMessageInChannel($user, $content, $channel);
            }

            return response(['status' => 'You need to be in group to send messages'], 403);
        }

        return response(['status' => 'Group does not exist'], 403);
    }

    public function sendInEvent(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('id');
        $content = $request->input('content');
        $event = Event::find($event_id);

        if (is_object($event)) {
            $pivot = $event->users()
                   ->where('status', 'accepted')
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($pivot)) {
                $channel = $event->channel;
                return MessageFunctions::sendMessageInChannel($user, $content, $channel);
            }

            return response(['status' => 'You need to be in event to send messages'], 403);
        }

        return response(['status' => 'Event does not exist'], 403);
    }

    public function getUserMessages(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input('id');

        $results = [];
        $channel = ChannelFunctions::getUserChannel($user, User::find($friend_id));
        if (is_object($channel)) {
            foreach ($channel->messages()->get() as $message) {
                $results[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'user_id' => $message->user->id,
                    'date' => $message->created_at
                ];
            }
        }

        return response($results, 200);
    }

    public function getGroupMessages(Request $request) {
        $user = Auth::user();
        $group_id = $request->input('id');
        $group = Group::find($group_id);

        if (is_object($group)) {
            $results = [];
            
            $pivot = $group->users()
                   ->where('status', 'accepted')
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($pivot)) {
                $channel = $group->channel;
                foreach ($channel->messages()->get() as $message) {
                    $results[] = [
                        'id' => $message->id,
                        'content' => $message->content,
                        'user_id' => $message->user->id,
                        'date' => $message->created_at
                    ];
                }

                return response($results, 200);
            }

            return response(['status' => 'Access denied to messenger'], 200);
        }
        
        return response(['status' => 'Group does not exist', 403]);
    }

    public function getEventMessages(Request $request) {
        $user = Auth::user();
        $event_id = $request->input('id');
        $event = Event::find($event_id);

        if (is_object($event)) {
            $results = [];
            
            $pivot = $event->users()
                   ->where('status', 'accepted')
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($pivot)) {
                $channel = $event->channel;
                foreach ($channel->messages()->get() as $message) {
                    $results[] = [
                        'id' => $message->id,
                        'content' => $message->content,
                        'user_id' => $message->user->id,
                        'date' => $message->created_at
                    ];
                }

                return response($results, 200);
            }

            return response(['status' => 'Access denied to messenger'], 200);
        }
        
        return response(['status' => 'Event does not exist', 403]);
    }
}

