<?php
namespace App\Classes;
use App\Models\Event;
use App\Models\Group;
use App\Models\Location;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use App\Models\User;
use App\Models\Channel;
use App\Classes\PhotoFunctions;
use App\Classes\InputFunctions;
use App\Notifications\EventAdded;
use App\Notifications\EventInvite;
use App\Notifications\EventInviteAccepted;

class EventFunctions
{    
    public static function add($user, Request $request) {

        $data = $request->all();

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

        $channel = new Channel();
        $channel->save();

        $event_type = EventCategory::where('name', $data['type'])
                    ->first();

        $event->channel()->associate($channel);
        
        $event->location()->associate($location);

        $event->category()->associate($event_type);
        
        if ($event->save()) {
            
            $user->events()->attach($event->id, [
                'status' => 'accepted',
                'admin' => true
            ]);

            /*
            ** Notify Slack that an event has been created
            */
            $user->notify(new EventAdded($user, $event));

            /*
            ** Invite users associated to field invites in the new created event
            ** Either { type:group, id:int }, either { type:user, id:int }
            */
            $groups_id = [];
            $users_id = [];
            if ($request->has('invites')
                && !empty($invites = $request->input('invites'))) {

                if (array_key_exists('users', $invites)) {
                    $users_id = $invites['users'];
                }

                if (array_key_exists('groups', $invites)) {
                    $groups_id = $invites['groups'];
                }
                
                if (!empty($users_id)) {
                    EventFunctions::invite($user, $event->id, $users_id);
                }
                if (!empty($groups_id)) {
                    EventFunctions::link_groups($user, $groups_id, $event);
                }
            }

            /*
            ** Process Hashtags in description
            */
            InputFunctions::parse($event, $event->description);
            
        } else {
            return response('error while saving event', 403);
        }
        
        return response(['event_id' => $event->id], 200);
    }

    
    
    public static function join($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)
            && !$user->events->contains($event_id)) {
            if ($event->public) {
                $pivot = [
                    'status' => 'accepted',
                    'admin' => false
                ];
                $status = 'Event joined successfully';
            } else {
                $pivot = [
                    'status' => 'pending',
                    'admin' => false
                ];
                $status = 'Join request sent, waiting for accept';
            }
            
            $user->events()->attach($event_id, $pivot);
            return response(['status' => $status], 200);
        }
        return response(['status' => 'Event does not exist or invite already exists'], 403);
    }

    public static function invite($user, $event_id, $users_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            if ($user->events->contains($event_id)) {
                foreach ($users_id as $user_id) {
                    if (!$event->users->contains($user_id)) {
                        $event->users()->attach($user_id, [
                            'status' => 'invited',
                            'admin' => false
                        ]);
                        $invitedUser = User::find($user_id);
                        $invitedUser->notify(new EventInvite($user, $event, 'database'));
                        $user->notify(new EventInvite($invitedUser, $event, 'slack'));
                    }
                }

                return response(['status' => 'Invites sent'], 200);
            }
            return response(['status' => 'Access refused'], 403);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function refuseInvite($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            $event->users()->detach($user->id);

            $admin = $event->users()
                   ->where('admin', '=', true)
                   ->first();

            // TODO : Notify slack and admin that invited
            return response(['status' => 'Invite refused'], 200);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function acceptInvite($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            $event->users()->updateExistingPivot($user->id, [
                'status' => 'accepted'
            ]);
            $admin = $event->users()
                   ->where('admin', '=', true)
                   ->first();
            $user->notify(new EventInviteAccepted($admin, $event, 'slack'));
            $admin->notify(new EventInviteAccepted($user, $event, 'database'));
            return response(['status' => 'Invite accepted'], 200);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function acceptPrivateJoin($currentUser, $user_id, $event_id) {
        $event = Event::join('event_user', function ($join) {
            $join->on('events.id', '=', 'event_user.event_id');
        })
               ->where('event_user.user_id', '=', $currentUser->id)
               ->find($event_id);
        $userToAccept = User::find($user_id);

        if (!is_object($event)) {
            return response(['status' => 'Event does not exist'], 403);
        }
        
        if ($event->admin) {
            $userToAccept->events()->updateExistingPivot($event_id, [
                'status' => 'accepted',
                'admin' => false
            ]);
            return response(['status' => 'Event join request sent'], 200);
        } else {
            return response(['status' => 'Access refused, need to be admin'], 403);
        }
    }

    public static function infos($user, $event_id) {
        $event = Event::find($event_id);

        if (!is_object($event)) {
            return response('Event does not exist', 403);
        }
     
        $data = [];
        $profile_pic = $event->profile_pic()->first();
        $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'regular');
          
        $data['name'] = $event->name;
        $data['profile_pic_regular'] = $profile_pic_path;
        $data['description'] = $event->description;
        $data['public'] = $event->public;
        $data['date'] = $event->start;
        $data['expires'] = $event->expires;
        $data['address'] = '';
        $data['public'] = $event->public;
        $data['lat'] = $event->location->lat;
        $data['lng'] = $event->location->lng;
        $data['count'] = $event->users()
                       ->where('status', 'accepted')
                       ->get()
                       ->count();

        $belong = $user->events()
                ->where('events.id', '=', $event_id)
                ->first();
        
        if (is_object($belong)) {
            $data['invited'] = ($belong->pivot->status == 'invited');
            $data['belong'] = ($belong->pivot->status == 'accepted');
        } else {
            $data['invited'] = false;
            $data['belong'] = false;
        }
        
        $admin = $event->users()
               ->where('admin', '=', true)
               ->first();
        $data['admin_id'] = $admin->id;
        $profile_pica = $admin->profile_pic()->first();
        $profile_pica_path = PhotoFunctions::getUrl($profile_pica, 'avatar');
   
        $data['admin_url'] = $profile_pica_path;
        $data['admin_name'] = $admin->nickname;
        
        return response($data, 200);
    }

    public static function profile_pic($user, $encode, $event_id) {
        $event = $user->events()->where('events.id', '=', $event_id)->first();
        if (!is_object($event)) {
            return response([ 'error' => 'access refused'], 403);
        }

        if (!$event->pivot->admin) {
            return response(['error' => 'You need to be admin'], 403);
        }
        $decode = base64_decode($encode);
        $md5 = md5($decode);

        /*
        ** Check photo already exists
        */
        $photo = Photo::where('md5', $md5)->first();
        if(is_object($photo)) {
            return response(['refused' => 'Photo already exists'], 403);
        }

        $photo = PhotoFunctions::uploadImage($user, $md5, $decode);
        $photo->save();

        $event = Event::find($event_id);
        $event->profile_pic()->associate($photo);
        $event->save();

        return response(['photo_id' => $photo->id], 200);
    }

    public static function link($user, $event_id, $invites) {
        $event = Event::find($event_id);
        if (is_object($event)) {

            $users_id = [];
            $groups_id = [];
            
            if (array_key_exists('users', $invites)) {
                foreach ($invites['users'] as $userInvited) {
                    $users_id[] = $userInvited;
                }
            }

            if (array_key_exists('groups', $invites)) {
                foreach ($invites['groups'] as $groupInvited) {
                    $groups_id[] = $groupInvited;
                }
            }

            if (!empty($users_id)) {
                EventFunctions::invite($user, $event->id, $users_id);
            }
            if (!empty($groups_id)) {
                EventFunctions::link_groups($user, $groups_id, $event);
            }

            return response(['status' => 'Success'], 200);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function unlink($user, $event_id, $unlinks) {
        $event = Event::find($event_id);
        if (is_object($event)) {

            $pivot = $event->users()
                   ->where('users.id', $user->id)
                   ->where('admin', true)
                   ->first();

            // Check if admin with pivot
            if (is_object($pivot)) {
                if (array_key_exists('users', $unlinks)) {
                    foreach ($unlinks['users'] as $user_id) {
                        $currUser = $event->users()
                                  ->where('users.id', $user_id)->first();

                        // Unlink only non-admin users
                        if (!$currUser->pivot->admin) {
                            $event->users()->detach($user_id);
                        }
                        // Notif slack user unlinked
                    }
                }

                if (array_key_exists('groups', $unlinks)) {
                    foreach ($unlinks['groups'] as $group_id) {
                        $event->groups()->detach($group_id);
                        // Notif slack group unlinked
                    }
                }
                return response(['status' => 'Success'], 200);
            }
            return response(['status' => 'Need to be admin'], 200);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function photos($user, $event_id) {
        $event = Event::find($event_id);
        if (!is_object($event)) {
            return response(['status' => 'Event does not exists'], 403);
        }

        $response = [];
        foreach ($event->photos as $photo) {
            
            $response[] = [
                'id' => $photo->id,
                'lat' => $photo->location->lat,
                'lng' => $photo->location->lng,
                'path' => PhotoFunctions::getUrl($photo, 'regular')
            ];
        }

        return response($response, 200);
    }

    public static function link_groups($user, $groups_id, $event) {
        $groups = Group::whereIn('groups.id', $groups_id)->get();
        foreach ($groups as $group) {

            $pivot = $group->users()
                   ->where('users.id', $user->id)
                   ->where('admin', true)
                   ->first();
            // User need to be admin
            if (is_object($pivot)) {
                $group->events()->attach($event->id);
            
                if ($group->users->contains($user->id)) {
                    EventFunctions::invite($user,
                                           $event->id,
                                           $group->users()
                                           ->where('users.id', '!=', $user->id)
                                           ->get()->pluck('id'));
                }
            }
        }

        return response(['status' => 'Invites sent'], 200);
    }

    public static function quit($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            if ($event->users->contains($user->id)) {
                $event->users()->detach($user->id);
                return response(['status' => 'Event quit successfully'], 200);
            }
            return response(['status' => 'User does not belong to event'], 403);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function edit($user, $data) {
        $event = Event::find($data['id']);
        if (is_object($event)) {
            if ($event->users->contains($user->id)) {

                $currentRelation = $event->users()
                                 ->where('users.id', '=', $user->id)
                                 ->first();
                if ($currentRelation->pivot->admin) {
                    if (array_key_exists('name', $data)) {
                        $event->name = $data['name'];
                    }
                    if (array_key_exists('description', $data)) {
                        $event->description = $data['description'];
                    }
                    if (array_key_exists('start', $data)) {
                        $event->start = $data['start'];
                    }
                    if (array_key_exists('expires', $data)) {
                        $event->expires = $data['expires'];
                    }
                    if (array_key_exists('lat', $data)) {
                        $event->location->lat = $data['lat'];
                    }
                    if (array_key_exists('lng', $data)) {
                        $event->location->lng = $data['lng'];
                    }
                    if (array_key_exists('public', $data)) {
                        $event->public = $data['public'];
                    }

                    $event->save();
                    
                    return response(['status' => 'Edit successfull'], 200);
                }
                return response(['status' => 'User is not admin'], 403);
            }
            return response(['status' => 'User does not belong to event'], 403);
        }
        return response(['status' => 'Event does not exist'], 403);
    }

    public static function getRelatedContacts($user,
                                              $event_id,
                                              $name_begin,
                                              $exclude) {
        $event = Event::find($event_id);

        $groups = $event->groups();
        $users = $event->users()
               ->where('status', 'accepted');

        if ($name_begin) {
            $groups->where('name', 'like', '%' . $name_begin);
            $users->where('nickname', 'like', '%' . $name_begin);
        }

        $groups = $groups->get();
        $users = $users->get();
        
        if (is_object($event)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $group) {
                $profile_pic = $group->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $response['groups'][] = [
                    'id' => $group->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $group->name,
                    'is_contact' => ($group->users->contains($user->id)
                                     ? true : false)
                ];
            }

            foreach ($users as $contact) {
                $profile_pic_path = null;
                $profile_pic = $contact->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }

                $firstname = null;
                $lastname = null;
                $is_contact = false;
                if ($user->getFriends->contains($user->id)) {
                    $firstname = $contact->firstname;
                    $lastname = $contact->lastname;
                    $is_contact = true;
                }
                
                $response['users'][] = [
                    'id' => $contact->id,
                    'profile_pic' => $profile_pic_path,
                    'nickname' => $contact->nickname,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'is_contact' => $is_contact
                ];
            }

            return response($response, 200);
            
        }
        return response(['status' => 'Group does not exists'], 403);
    }
}
