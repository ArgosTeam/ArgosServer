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
use PhotoFunctions;

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
            /*
            ** Create hashtag if not exist
            ** Associate hashtag to event
            */
            foreach ($request->input('hashtags') as $name) {
                $hashtag = Hashtag::where('name', '=', $name)
                         ->first();
                if (!is_object($hashtag)) {
                    $hashtag = Hashtag::create([
                        'name' => $name
                    ]);
                }
                $hashtag->events()->attach($event->id);
            }
            
            $user->events()->attach($event->id, [
                'status' => 'accepted',
                'admin' => true
            ]);
            return response(['event_id' => $event->id], 200);
        } else {
            return response('error while saving event', 404);
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

    public static function infos($user, $event_id) {
        $event = Event::find($event_id);

        if (!is_object($event)) {
            return response('Event does not exist', 404);
        }
        $belong = $user->events()
                ->where('events.id', '=', $event_id)
                ->first();

        $data = [];
        $data['name'] = $event->name;
        $data['profile_pic'] = '';
        $data['description'] = $event->description;
        $data['hashtags'] = [];
        foreach ($event->hashtags()->get() as $hashtag) {
            $data['hashtags'][] = [
                'id' => $hashtag->id,
                'name' => $hashtag->name
            ];
        }
        $data['date'] = $event->start;
        $data['expires'] = $event->expires;
        $data['address'] = '';
        if (is_object($belong)) {
            $data['participate'] = ($belong->pivot->status === 'accepted' ? true : false);
            $data['pending'] = ($belong->pivot->status === 'pending' ? true : false);
            $data['admin'] = $belong->pivot->admin;
        } else {
            $data['participate'] = false;
            $data['pending'] = false;
            $data['admin'] = false;
        }
        $admin = $event->users()
               ->where('admin', '=', true)
               ->first();
        $data['admin_id'] = $admin->id;
        $data['admin_name'] = $admin->firstName . ' ' . $admin->lastName;
        
        return response($data, 200);
    }

    public static function comment($user, $event_id, $content) {
        $event = Event::find($event_id);
        if (!is_object($event)) {
            return response('Event does not exist', 404);
        }
        $comment = new Comment();
        $comment->content = $content;
        $comment->user()->associate($user);
        if ($comment->save()) {
            $comment->events()->attach($event->id);
            return response(['comment_id' => $comment->id], 200);
        } else {
            return response('Error while saving', 404);
        }
    }

    public static function profile_pic($user, $encode, $event_id) {
        if (!$user->events->contains($event_id)) {
            return response([ 'error' => 'access to the event refused'], 404);
        }
        
        $decode = base64_decode($encode);
        $md5 = md5($decode);

        /*
        ** Check photo already exists
        */
        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)) {
            return response(['refused' => 'Photo already exists'], 404);
        }

        $photo = PhotoFunctions::uploadImage($user, $md5, $decode);
        $photo->save();

        $event = Event::find($event_id);
        $event->profile_pic()->associate($photo);

        return response(['photo_id' => $photo->id], 200);
    }
}
