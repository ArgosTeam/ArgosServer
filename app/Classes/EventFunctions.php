<?php
namespace App\Classes;
use App\Http\Requests\SubmitEventCreate;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Event;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Location;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\User;

/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 26/09/2016
 * Time: 6:56 AM
 */

class EventFunctions
{

    public static function add($user, Request $request) {

        $data = $request->all();

        $event = Event::where('name', '=', $data['name'])
               ->first();
        if (is_object($event)) {
            return response('Event alreay exists', 404);
        }

        $event = new Event();
        $event->name = $data['name'];
        $event->description = $data['description'];
        $event->user_id = $user->id;
        $event->public = $data['public'];
        $event->start = $data['start'];

        if(array_key_exists("expires", $data)) {
            $event->expires = $data['expires'];
        }

        $location = new Location([
            "lat" => $data["lat"],
            "lng" => $data["lng"],
        ]);
        $location->save();
        $event->location()->associate($location);
        
        if ($event->save()) {
            $user->events()->attach($event->id, [
                'status' => 'accepted',
                'admin' => true
            ]);
            return (["status" => "created", "event_id" => $event->id]);
        } else {
            return (["status" => "error while saving event"]);
        }
    }

    public static function join($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)
            && !$user->events->contains($event_id)) {
            $user->events()->attach($event_id, [
                'status' => 'pending',
                'admin' => false
            ]);
            return response('Join request sent', 200);
        }
        return response('Event does not exist or invite already exists', 404);
    }

    public static function accept($currentUser, $user_id, $event_id) {
        $event = Event::join('event_user', function ($join) {
            $join->on('events.id', '=', 'event_user.event_id');
        })
               ->where('event_user.user_id', '=', $currentUser->id)
               ->find($event_id);
        $userToAccept = User::find($user_id);
        
        if ($event->admin) {
            $userToAccept->events()->updateExistingPivot($event_id, [
                'status' => 'accepted',
                'admin' => false
            ]);
            return response('Event join request sent', 200);
        } else {
            return response('Access refused, need to be admin', 404);
        }
    }

}
