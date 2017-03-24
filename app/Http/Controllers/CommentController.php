<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\Comment;
use App\Classes\PhotoFunctions;

class CommentController extends Controller
{

    public function event(Request $request) {
        $event_id = $request->input('id');
        $event = Event::find($event_id);
        if (is_object($event)) {
            $comments = $this->getComments($event);
            return response($comments, 200);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public function photo(Request $request) {
        $photo_id = $request->input('id');
        $photo = Photo::find($photo_id);
        if (is_object($photo)) {
            $comments = $this->getComments($photo);
            return response($comments, 200);
        }
        return response(['status' => 'Photo does not exist'], 403);
    }

    public function group(Request $request) {
        $group_id = $request->input('id');
        $group = Group::find($group_id);
        if (is_object($group)) {
            $comments = $this->getComments($group);
            return response($comments, 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    private function getComments($item) {
        $comments = $item->comments()->get();
        $response = [];

        foreach ($comments as $comment) {
            $user = $comment->user()->first();
            $profile_pic_path = null;
            $profile_pic = $user->profile_pic()->first();
            if (is_object($profile_pic)) {
                $request = PhotoFunctions::getUrl($profile_pic);
                $profile_pic_path = '' . $request->getUri() . '';
            }
            $response[] = [
                'id' => $comment->id,
                'content' => $comment->content,
                'rate' => 0,
                'user_id' => $user->id,
                'user_profile_pic' => $profile_pic_path,
                'user_nickname' => $user->nickname
                ];
        }
        
        return $response;
    }
}