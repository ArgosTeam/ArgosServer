<?php
namespace App\Classes;
use App\Models\Group;
use App\Models\User;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Channel;
use App\Classes\PhotoFunctions;
use Illuminate\Support\Facades\Storage;
use App\Notifications\GroupAdded;
use App\Notifications\GroupInvite;
use App\Notifications\GroupInviteAccepted;

class GroupFunctions
{
    public static function add($user, $request) {
        
        if(is_object($user)) {

            $group = new Group();
            $group->name = $request->input('name');
            $group->public = $request->input('public');
            $group->description = $request->input('description');
            $group->address = $request->input('address');
            
            // $location = new Location([
            //     'lat' => $request->input('lat'),
            //     'lng' => $request->input('lng')
            // ]);
 
            $channel = new Channel();
            $channel->save();

            //$location->save();
            //$group->location()->associate($location);
            $group->channel()->associate($channel);
            $group->save();

            $user->groups()->attach($group->id, [
                'status' => 'accepted',
                'admin' => true
            ]);

            /*
            ** Notify Slack that a group has been created
            */
            $user->notify(new GroupAdded($user, $group));
            
            /*
            ** Invite users associated to field invites in the new created group
            */
            $groups_id = [];
            $users_id = [];
            if ($request->has('invites')
                && !empty($invites = $request->input('invites'))) {


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
                    GroupFunctions::invite($user, $group->id, $users_id);
                }
                if (!empty($groups_id)) {
                    GroupFunctions::link_groups($user, $groups_id, $group);
                }
            }

            /*
            ** Process Hashtags in description
            */
            InputFunctions::parse($group, $group->description);
            
            return response(['group_id' => $group->id], 200);
        } else {
            return response('User not found', 403);
        }
    }

    public static function join($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)
            && !$user->groups->contains($group_id)) {
            if ($group->public) {
                $pivot = [
                    'status' => 'accepted',
                    'admin' => false
                ];
                $status = 'Group joined successfully';
            } else {
                $pivot = [
                    'status' => 'pending',
                    'admin' => false
                ];
                $status = 'Join request sent, waiting for accept';
            }
            
            $user->groups()->attach($group_id, $pivot);
            return response(['status' => $status], 200);
        }
        return response(['status' => 'Group does not exist or invite already exists'], 403);
    }

    public static function invite($user, $group_id, $users_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            if ($user->groups->contains($group_id)) {
                foreach ($users_id as $user_id) {
                    if (!$group->users->contains($user_id)) {
                        $group->users()->attach($user_id, [
                            'status' => 'invited',
                            'admin' => false
                        ]);
                        $invitedUser = User::find($user_id);
                        $invitedUser->notify(new GroupInvite($user, $group, 'database'));
                        $user->notify(new GroupInvite($invitedUser, $group, 'slack'));
                    }
                    
                }

                return response(['status' => 'Invites sent'], 200);
            }
            return response(['status' => 'Access refused'], 403);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    public static function refuseInvite($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            $group->users()->detach($user->id);

            $admin = $group->users()
                   ->where('admin', '=', true)
                   ->first();

            // TODO : Notify slack and admin that invited
            return response(['status' => 'Invite refused'], 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }
    
    public static function acceptInvite($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            $group->users()->updateExistingPivot($user->id, [
                'status' => 'accepted'
            ]);

            $admin = $group->users()
                   ->where('admin', '=', true)
                   ->first();
            $user->notify(new GroupInviteAccepted($admin, $group, 'slack'));
            $admin->notify(new GroupInviteAccepted($user, $group, 'database'));            
            return response(['status' => 'Invite accepted'], 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }
    
    public static function acceptPrivateJoin($currentUser, $user_id, $group_id) {
        $group = Group::join('group_user', function ($join) {
            $join->on('groups.id', '=', 'group_user.group_id');
        })
               ->where('group_user.user_id', '=', $currentUser->id)
               ->find($group_id);
        $userToAccept = User::find($user_id);
        
        if (is_object($group) && $group->admin) {
            $userToAccept->groups()->updateExistingPivot($group_id, [
                'status' => 'accepted',
                'admin' => false
            ]);
            return response(['status' => 'Accepted'], 200);
        } else {
            return response(['status' => 'Access refused, need to be admin, or group does not exist'], 403);
        }
    }

    public static function infos($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            
            $data = [];
            $profile_pic = $group->profile_pic()->first();
            $profile_pic_path = null;
            
            if (is_object($profile_pic)) {
                $profile_pic_path = PhotoFunctions::getUrl($profile_pic, 'regular');
            }

            if ($user->belongsToGroup($group_id)) {
                $groups = $group->groups()->get();
                $photos = $group->photos()->get();
            } else {
                $groups = GroupFunctions::getGroupsOnEventGroupProfile($group, $user, null);
                $photos = GroupFunctions::getPhotosOnProfile($group, $user);
            }
            
            $data['group_id'] = $group_id;
            $data['profile_pic_regular'] = $profile_pic_path;
            $data['name'] = $group->name;
            $data['public'] = $group->public;
            $data['address'] = $group->address;
            $data['description'] = $group->description;
            $data['date'] = $group->created_at;

            $belong = $group->users()
                    ->where('users.id', $user->id)
                    ->first();

            if (is_object($belong)) {
                $data['invited'] = ($belong->pivot->status == 'invited');
                $data['belong'] = ($belong->pivot->status == 'accepted');
            } else {
                $data['invited'] = false;
                $data['belong'] = false;
            }

            $admin = $group->users()
                   ->where('admin', true)
                   ->first();

            $profile_pic_path = null;
            if (is_object($profile_pic = $admin->profile_pic()->first())) {
                $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
            }

            $data['admin_id'] = $admin->id;
            $data['admin_nickname'] = $admin->nickname;
            $data['admin_url'] = $profile_pic_path;

            $data['events_count'] = $group->events->count();
            $data['users_count'] = $group->users()
                                 ->where('status', 'accepted')
                                 ->count();
            $data['groups_count'] = $groups->count();
            $data['photos_count'] = $photos->count();
            $data['messages_count'] = $group->channel->messages->count();
            
            return response($data, 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    public static function profile_pic($user, $encode, $group_id) {
        $group = $user->groups()->where('groups.id', '=', $group_id)->first();
        if (!is_object($group)) {
            return response([ 'error' => 'access refused'], 403);
        }

        if (!$group->pivot->admin) {
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

        $group = Group::find($group_id);
        $group->profile_pic()->associate($photo->id);
        $group->save();

        return response(['photo_id' => $photo->id], 200);
    }

    /*
    ** Methods to get the public display mode of group/photos (user does not belong to group)
    ** If user belongs to group, it can access all datas of the group with group->elem()
    */
    public static function getPhotosOnProfile($user, $group) {
        // get private pictures that user can see
        $private_pictures = $group->photos()
                          ->where('public', false)
                          ->whereHas('users', function ($query) use ($user) {
                              $query->where('users.id', $user->id)
                                  ->where('status', 'accepted');
                          })
                          ->get();

        $public_pictures = $group->photos()
                         ->where('public', true)
                         ->get();

        $photos = $private_pictures->merge($public_pictures);
        return $photos;
    }
    
    public static function photos($user, $group_id) {
        $group = Group::find($group_id);
        if (!is_object($group)) {
            return response(['status' => 'Group does not exists'], 403);
        }

        $photos = GroupFunctions::getPhotosOnProfile($user, $group);

        $response = [];
        foreach ($photos as $photo) {
            $response[] = [
                'id' => $photo->id,
                'lat' => $photo->location->lat,
                'lng' => $photo->location->lng,
                'description' => $photo->description,
                'path' => PhotoFunctions::getUrl($photo, 'regular'),
                'public' => $photo->public,
                'mode' => $photo->mode,
                'admin' => $photo->pivot->admin
            ];
        }

        return response($response, 200);
    }

    public static function link($user, $group_id, $invites) {
        $group = Group::find($group_id);
        if (is_object($group)) {

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
                GroupFunctions::invite($user, $group->id, $users_id);
            }
            if (!empty($groups_id)) {
                GroupFunctions::link_groups($user, $groups_id, $group);
            }

            return response(['status' => 'Success'], 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }
    
    public static function link_groups($user, $groups_id, $group) {
        $groups = Group::whereIn('groups.id', $groups_id)->get();
        foreach ($groups as $groupToInvite) {

            /*
            ** Update both side contacts
            ** Need to be admin
            */
            $pivot = $groupToInvite->users()
                   ->where('users.id', $user->id)
                   ->where('admin', true)
                   ->first();
            if (is_object($pivot)) {
                $group->groups()->attach($groupToInvite->id);
                $groupToInvite->groups()->attach($group->id);
                
                if ($groupToInvite->users->contains($user->id)) {
                    GroupFunctions::invite($user,
                                           $group->id,
                                           $groupToInvite->users()
                                           ->where('users.id', '!=', $user->id)
                                           ->get()->pluck('id'));
                }
            }
        }

        return response(['status' => 'Invites sent'], 200);
    }

    public static function quit($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            if ($group->users->contains($user->id)) {
                /*
                 *  If user is admin, set the next user to admin
                 *  If no users left, delete the group 
                 */
                $pivotUser = $user->groups()
                       ->where('group_id', $group_id)
                       ->first();

                if ($pivotUser->pivot->admin) {
                    if ($group->users()->where('status', 'accepted')->count() > 1) {
                        $nextUser = $group->users()
                                  ->where('users.id', '!=', $user->id)
                                  ->where('status', 'accepted')
                                  ->first();
                        $nextUser->groups()->updateExistingPivot($group->id, [
                            'admin' => true
                        ]);
                    } else {
                        // No more users in group
                        $group->delete();
                        return response(['status' => 'Group quit and deleted successfully'], 200);
                    }
                }
                
                $group->users()->detach($user->id);
                return response(['status' => 'Group quit successfully'], 200);
            }
            return response(['status' => 'User does not belong to group'], 403);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    public static function unlink($user, $group_id, $unlinks) {
        $group = Group::find($group_id);
        if (is_object($group)) {

            $pivot = $group->users()
                   ->where('users.id', $user->id)
                   ->where('admin', true)
                   ->first();

            // Check if admin with pivot
            if (is_object($pivot)) {
                if (array_key_exists('users', $unlinks)) {
                    foreach ($unlinks['users'] as $user_id) {
                        $currUser = $group->users()
                                  ->where('users.id', $user_id)->first();

                        // Unlink only non-admin users
                        if (!$currUser->pivot->admin) {
                            $group->users()->detach($user_id);
                        }
                        // Notif slack user unlinked
                    }
                }

                if (array_key_exists('groups', $unlinks)) {
                    foreach ($unlinks['groups'] as $group_id) {
                        $group->groups()->detach($group_id);
                        // Notif slack group unlinked
                    }
                }
                return response(['status' => 'Success'], 200);
            }
            return response(['status' => 'Need to be admin'], 200);
        }
        return response(['status' => 'Group does not exist'], 403);
    }
    
    public static function edit($user, $data) {
        $group = Group::find($data['id']);
        if (is_object($group)) {
            if ($group->users->contains($user->id)) {

                $currentRelation = $group->users()
                                 ->where('users.id', '=', $user->id)
                                 ->first();
                if ($currentRelation->pivot->admin) {
                    if (array_key_exists('name', $data)) {
                        $group->name = $data['name'];
                    }
                    if (array_key_exists('description', $data)) {
                        $group->description = $data['description'];
                    }
                    // if (array_key_exists('lat', $data)) {
                    //     $group->location->lat = $data['lat'];
                    // }
                    // if (array_key_exists('lng', $data)) {
                    //     $group->location->lng = $data['lng'];
                    // }
                    if (array_key_exists('public', $data)) {
                        $group->public = $data['public'];
                    }
                    
                    $group->save();

                    return response(['status' => 'Edit successfull'], 200);
                }
                return response(['status' => 'User is not admin'], 403);
            }
            return response(['status' => 'User does not belong to group'], 403);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    public static function getRelatedContacts($user,
                                              $group_id,
                                              $name_begin,
                                              $exclude) {
        $group = Group::find($group_id);

        $groups = GroupFunctions::getGroupsOnEventGroupProfile($group, $user, $name_begin);
        $users = $group->users()
               ->where('status', 'accepted');

        if ($name_begin) {
            $users->where('nickname', 'like', '%' . $name_begin);
        }

        $users = $users->get();
        
        if (is_object($group)) {
            $response = ['groups' => [], 'users' => []];
            foreach ($groups as $groupContact) {
                $profile_pic_path = null;
                $profile_pic = $groupContact->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $response['groups'][] = [
                    'id' => $groupContact->id,
                    'profile_pic' => $profile_pic_path,
                    'name' => $groupContact->name,
                    'is_contact' => ($groupContact->users->contains($user->id)
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

    public static function events($user,
                                  $group_id,
                                  $name_begin,
                                  $exclude) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            $response = [];
            $events = $group->events();
            if ($name_begin) {
                $events->where('name', 'like', '%' . $name_begin);
            }
            $events = $events->get();
            
            foreach ($events as $event) {

                $profile_pic_path = null;
                $profile_pic = $event->profile_pic()->first();
                if (is_object($profile_pic)) {
                    $profile_pic_path = PhotoFunctions::getUrl($profile_pic);
                }
                $pivot = $user->events()
                       ->where('status', 'accepted')
                       ->where('events.id', $event->id)
                       ->first();
                $response[] = [
                    'event_id' => $event->id,
                    'profile_pic' => $profile_pic_path,
                    'event_name' => $event->name,
                    'invited' => ((is_object($pivot) && $pivot->status == 'invited')
                                  ? true : false),
                    'accepted' => ((is_object($pivot) && $pivot->status == 'accepted')
                                   ? true : false),
                    'date' => $event->start
                ];
            }

            return response($response, 200);
        }
        
        return response(['status' => 'Group does not exist'], 403);
    }

    // User Profile, get groups.
    public static function getGroupsOnUserProfile($userProfile, $user, $name_begin) {
        // Only prvate groups that user has already joined can be displayed on a profile
        $private_groups = $userProfile->groups()
                        ->where('public', false)
                        ->where('status', 'accepted')
                        ->whereHas('users', function ($query) use ($user) {
                            $query->where('users.id', $user->id)
                                ->where('status', 'accepted');
                        });

        $public_groups = $userProfile->groups()
                         ->where('public', true)
                         ->where('status', 'accepted');

        if ($name_begin) {
            $private_groups->where('name', 'like', $name_begin . '%');
            $public_groups->where('name', 'like', $name_begin . '%');
        }

        $private_groups = $private_groups->get();
        $public_groups = $public_groups->get();
        
        return $public_groups->merge($private_groups);
    }

    // Event and Group profile get groups contacts if not in event/group
    public static function getGroupsOnEventGroupProfile($element, $user, $name_begin) {
        // Only prvate groups that user has already joined can be displayed on a profile
        $private_groups = $element->groups()
                        ->where('public', false)
                        ->whereHas('users', function ($query) use ($user) {
                            $query->where('users.id', $user->id)
                                ->where('status', 'accepted');
                        });

        $public_groups = $element->groups()
                       ->where('public', true);
        
        if ($name_begin) {
            $private_groups->where('name', 'like', $name_begin . '%');
            $public_groups->where('name', 'like', $name_begin . '%');
        }

        $private_groups = $private_groups->get();
        $public_groups = $public_groups->get();
        
        return $public_groups->merge($private_groups);
    }
}
