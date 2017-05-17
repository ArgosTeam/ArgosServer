<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use App\Models\Photo;
use App\Models\Event;
use App\Models\RatingType;
use App\Models\EventRating;
use App\Models\GroupRating;
use App\Models\PhotoRating;

class RatingController extends Controller
{

    public function rate(Request $request) {
        $user = Auth::user();
        $ratingType = RatingType::where('name', $request->input('name'))
              ->first();
        $id = $request->input('id');
        $objectType = $request->input('type');

        if ($objectType == 'photo') {
            
            $photo = Photo::find($id);
            $photoRating = PhotoRating::where('user_id', $user->id)
                         ->where('photo_id', $photo->id)
                         ->first();
            if (!is_object($photoRating)) {
                $photoRating = new PhotoRating();
                $photoRating->user()->associate($user->id);
                $photoRating->photo()->associate($photo->id);
            }
            
            $photoRating->rating_type()->associate($ratingType->id);
            $photoRating->save();

            return response(['status' => 'success'], 200);
        }

        if ($objectType == 'event') {
            $event = Event::find($id);
            $eventRating = EventRating::where('user_id', $user->id)
                         ->where('event_id', $event->id)
                         ->first();
            if (!is_object($eventRating)) {
                $eventRating = new EventRating();
                $eventRating->user()->associate($user->id);
                $eventRating->event()->associate($event->id);
            }
            
            $eventRating->rating_type()->associate($ratingType->id);
            $eventRating->save();

            return response(['status' => 'success'], 200);
        }

        if ($objectType == 'group') {
            $group = Group::find($id);
            $groupRating = GroupRating::where('user_id', $user->id)
                         ->where('group_id', $group->id)
                         ->first();
            if (!is_object($groupRating)) {
                $groupRating = new GroupRating();
                $groupRating->user()->associate($user->id);
                $groupRating->group()->associate($group->id);
                return response(['status' => 'Already rated'], 403);
            }
            
            $groupRating->rating_type()->associate($ratingType->id);
            $groupRating->save();

            return response(['status' => 'success'], 200);
        }
    }
}