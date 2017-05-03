<?php
namespace App\Classes;
use App\Models\Group;
use App\Models\User;
use App\Models\Hashtag;
use App\Models\Photo;
use App\Models\Message;
use App\Models\Channel;
use App\Classes\PhotoFunctions;

class MessageFunctions
{
    public static function sendMessageInChannel($user, $content, $channel) {
        $message = new Message([
            'content' => $content
        ]);

        $message->user()->associate($user->id);
        $message->channel()->associate($channel->id);
        $message->save();
        $channel->users()->updateExistingPivot($user->id, [
            'last_seen_message_id' => $message->id
        ]);
        return response(['status' => 'Message sent successfully'], 200);
    }
}