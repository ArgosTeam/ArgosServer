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
        $type = RatingType::where('name', $request->input('type'))
              ->first();
        $id = $request->input('id');

        if ($type->name == 'photo') {
            
            $photo = Photo::find($id);
            $photoRating = PhotoRating::where('user_id', $user->id)
                         ->where('photo_id', $photo->id)
                         ->first();
            if (is_object($photoRating)) {
                return response(['status' => 'Already rated'], 403);
            }
            
            $rate = new PhotoRating();
            $rate->rating_type()->attach($type->id);
            $rate->user()->attach($user->id);
            $rate->photo()->attach($photo->id);
            $rate->save();

            return response(['status' => 'success'], 200);
        }

        if ($type->name == 'event') {
            $event = Event::find($id);
            $eventRating = EventRating::where('user_id', $user->id)
                         ->where('event_id', $event->id)
                         ->first();
            if (is_object($eventRating)) {
                return response(['status' => 'Already rated'], 403);
            }
            
            $rate = new EventRating();
            $rate->rating_type()->attach($type->id);
            $rate->user()->attach($user->id);
            $rate->event()->attach($event->id);
            $rate->save();

            return response(['status' => 'success'], 200);
        }

        if ($type->name == 'group') {
            $group = Group::find($id);
            $groupRating = GroupRating::where('user_id', $user->id)
                         ->where('group_id', $group->id)
                         ->first();
            if (is_object($groupRating)) {
                return response(['status' => 'Already rated'], 403);
            }
            
            $rate = new GroupRating();
            $rate->rating_type()->attach($type->id);
            $rate->user()->attach($user->id);
            $rate->group()->attach($group->id);
            $rate->save();

            return response(['status' => 'success'], 200);
        }
    }
}