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
use App\Models\Photo;
use App\Classes\ChannelFunctions;
use App\Classes\MessageFunctions;
use App\Notifications\NewUserMessage;
use App\Classes\PhotoFunctions;

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
                $response = MessageFunctions::sendMessageInChannel($user, $content, $channel);
                $friend->notify(new NewUserMessage($user, $content));
                return $response;
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

    public function sendOnPhotoChat(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $content = $request->input('content');
        $photo = Photo::find($photo_id);

        if (is_object($photo)) {

            
            if (!is_object($photo->channel()->first())) {
                $channel = new Channel();
                $channel->save();
                $photo->channel()->associate($channel->id);
                $photo->save();
            }
            
            $channel = $photo->channel;
            return MessageFunctions::sendMessageInChannel($user, $content, $channel);
        }

        return response(['status' => 'Photo does not exist'], 403);
    }

    public function getUserMessages(Request $request) {
        $user = Auth::user();
        $friend_id = $request->input('id');

        $results = [];
        $channel = ChannelFunctions::getUserChannel($user, User::find($friend_id));
        $friend = $channel->users()
                ->where('users.id', '!=', $user->id)
                ->first();
        if (is_object($channel)) {
            foreach ($channel->messages()->get() as $message) {
                $last_seen = $friend->last_seen_message_id == $message->id;

                $profile_pic = $message->user->profile_pic()->first();
                $profile_pic_path = null;

                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'avatar');
                }
                
                $results[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'user_id' => $message->user->id,
                    'last_seen' => $last_seen,
                    'profile_pic' => $profile_pic_path,
                    'date' => $message->created_at
                ];
            }
            $latest_msg = $channel->messages()->latest()->first();
            $channel->users()->updateExistingPivot($user->id, [
                'last_seen_message_id' => $latest_msg->id
            ]);
        }

        return response(["content" => $results], 200);
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
                    $profile_pic = $message->user->profile_pic()->first();
                    $profile_pic_path = null;

                    if (is_object($profile_pic)) {
                        $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'avatar');
                    }
                    
                    $results[] = [
                        'id' => $message->id,
                        'content' => $message->content,
                        'user_id' => $message->user->id,
                        'profile_pic' => $profile_pic_path,
                        'date' => $message->created_at
                    ];
                }

                return response(["content" => $results], 200);
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

            // Check if user belongs to Event
            if (is_object($pivot)) {
                $channel = $event->channel;
                
                foreach ($channel->messages()->get() as $message) {
                    $profile_pic = $message->user->profile_pic()->first();
                    $profile_pic_path = null;
                    
                    if (is_object($profile_pic)) {
                        $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'avatar');
                    }
                    
                    $results[] = [
                        'id' => $message->id,
                        'content' => $message->content,
                        'user_id' => $message->user->id,
                        'profile_pic' => $profile_pic_path,
                        'date' => $message->created_at
                    ];
                }

                return response(["content" => $results], 200);
            }

            return response(['status' => 'Access denied to messenger'], 200);
        }
        
        return response(['status' => 'Event does not exist', 403]);
    }

    public function getPhotoMessages(Request $request) {
        $user = Auth::user();
        $photo_id = $request->input('id');
        $photo = Photo::find($photo_id);

        if (is_object($photo)) {
            $results = [];
            // TODO : CHECK RIGHTS 
            $channel = $photo->channel;
            foreach ($channel->messages()->get() as $message) {
                $profile_pic = $message->user->profile_pic()->first();
                $profile_pic_path = null;

                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'avatar');
                }
                
                $results[] = [
                    'id' => $message->id,
                    'content' => $message->content,
                    'user_id' => $message->user->id,
                    'profile_pic' => $profile_pic_path,
                    'date' => $message->created_at
                ];
            }
            
            return response(["content" => $results], 200);
            
        }
        
        return response(['status' => 'Photo does not exist', 403]);
    }
}

