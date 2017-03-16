<?php
namespace App\Classes;
use App\Models\Group;
use App\Models\User;
use App\Models\Hashtag;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Comment;
use App\Classes\PhotoFunctions;
use Illuminate\Support\Facades\Storage;
use App\Notifications\GroupAdded;
use App\Notifications\GroupInvite;
use App\Notifications\GroupInviteAccepted;

class GroupFunctions
{
    public static function add($user, $request) {
        $group = Group::where('name', '=', $request->input('name'))
               ->first();
        if (is_object($group)) {
            return response('This group name already exists', 403);
        }
        
        if(is_object($user)) {

            $group = new Group();
            $group->name = $request->input('name');
            $group->public = $request->input('public');
            $group->description = $request->input('description');
            $group->address = $request->input('address');
            
            $location = new Location([
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng')
            ]);

            $location->save();
            $group->location()->associate($location);
            $group->save();
            
            /*
            ** Create hashtag if not exist
            ** Associate hashtag to group
            */
            if ($request->has('hashtags')) {
                foreach ($request->input('hashtags') as $name) {
                    $hashtag = Hashtag::where('name', '=', $name)
                             ->first();
                    if (!is_object($hashtag)) {
                        $hashtag = Hashtag::create([
                            'name' => $name
                        ]);
                    }
                    $hashtag->groups()->attach($group);
                }
            }

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
            ** Either { type:group, id:int }, either { type:user, id:int }
            */
            $groups_id = [];
            $users_id = [];
            if ($request->has('invites')
                && !empty($invites = $request->input('invites'))) {


                if (array_key_exists('users', $invites)) {
                    foreach ($invites['users'] as $userInvited) {
                        $users_id[] = $userInvited->id;
                    }
                }

                if (array_key_exists('groups', $invites)) {
                    foreach ($invites['users'] as $groupInvited) {
                        $users_id[] = $groupInvited->id;
                    }
                }
                
                if (!empty($users_id)) {
                    GroupFunctions::invite($user, $group->id, $users_id);
                }
                if (!empty($groups_id)) {
                    GroupFunctions::link_groups($user, $groups_is, $group->id);
                }
            }

        } else {
            return response('User not found', 403);
        }

        return response(['group_id' => $group->id], 200);
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
                $request = PhotoFunctions::getUrl($profile_pic, 'regular');
                $profile_pic_path = '' . $request->getUri() . '';
            }
            
            $data['group_id'] = $group_id;
            $data['profile_pic'] = $profile_pic_path;
            $data['name'] = $group->name;
            $data['public'] = $group->public;
            $data['hashtags'] = [];
            $hashtags = $group->hashtags()->get();
            foreach ($hashtags as $hashtag) {
                $data['hashtags'] = [
                    'id' => $hashtag->id,
                    'name' => $hashtag->name
                ];
            }
            $data['address'] = $group->address;
            $data['date'] = $group->created_at;

            $belong =$group->users()
                    ->where('users.id', '=', $user->id)
                    ->first();
            
            if (is_object($belong)) {
                $data['belong'] = true;
                $data['admin'] = $belong->pivot->admin;
            } else {
                $data['belong'] = false;
                $data['admin'] = false;
            }

            $comments = [];
            foreach ($group->comments()->get() as $comment) {
                $currentUser = User::find($comment->user_id);
                $profile_pic = $currentUser->profile_pic()->first();
                $profile_pic_path = null;
                if (is_object($profile_pic)) {
                    $request = PhotoFunctions::getUrl($profile_pic, 'avatar');
                    $profile_pic_path = '' . $request->getUri() . '';
                }
                $comments[] = [
                    'content' => $comment->content,
                    'user_id' => $comment->user_id,
                    'user_url' => $profile_pic_path,
                    'user_name' => $currentUser->firstName . ' ' . $currentUser->lastName
                ];
            }
            $data['comments'] = $comments;
            
            return response($data, 200);
        }
        return response('Group does not exist', 403);
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

    public static function link_photo($user, $photo_id, $group_id) {
        $groups = Group::whereIn('groups.id', $group_id)->get();

        $photo = Photo::find($photo_id);
        if (!is_object($photo)) {
            return response('Photo does not exist');
        }
        if (!$photo->users->contains($user->id)) {
            return response(['status' => 'This photo does not belong to you'], 403);
        }

        foreach ($groups as $group) {
            if (!is_object($group)) {
                return response(['status' => 'Group does not exists'], 403);
            }
            if (!$group->users->contains($user->id)) {
                return response(['status' => 'Access to group denied'], 403);
            }

            if ($group->photos->contains($photo->id)) {
                return response('Photo already linked to group', 403);
            }
            $group->photos()->attach($photo->id);
        }

        return response(['status' => 'Photo linked to group'], 200);
    }

    public static function photos($user, $group_id) {
        $group = Group::find($group_id);
        if (!is_object($group)) {
            return response(['status' => 'Group does not exists'], 403);
        }

        $response = [];
        foreach ($group->photos as $photo) {

            $request = PhotoFunctions::getUrl($photo, 'regular');
            
            $response[] = [
                'photo_id' => $photo->id,
                'lat' => $photo->location->lat,
                'lng' => $photo->location->lng,
                'description' => $photo->description,
                'path' => '' . $request->getUri() . '',
                'public' => $photo->public,
                'origin_user_id' => $photo->origin_user_id
            ];
        }

        return response($response, 200);
    }

    public static function comment($user, $group_id, $content) {
        $group = Group::find($group_id);
        if (!is_object($group)) {
            return response('Group does not exist', 403);
        }
        $comment = new Comment();
        $comment->content = $content;
        $comment->user()->associate($user);
        if ($comment->save()) {
            $comment->groups()->attach($group->id);
            return response(['comment_id' => $comment->id], 200);
        } else {
            return response(['status' => 'Error while saving'], 403);
        }
    }

    public static function link_groups($user, $groups_id, $group_id) {
        $groups = Group::whereIn('groups.id', $groups_id)->get();
        foreach ($groups as $group) {
            if ($group->users->contains($user->id)) {
                GroupFunctions::invite($user,
                                       $group_id,
                                       $group->users()
                                       ->where('users.id', '!=', $user->id)
                                       ->get()->pluck('id'));
            }
        }

        return response(['status' => 'Invites sent'], 200);
    }

    public static function quit($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            if ($group->users->contains($user->id)) {
                $group->users()->detach($user->id);
                return response(['status' => 'Group quit successfully'], 200);
            }
            return response(['status' => 'User does not belong to group'], 403);
        }
        return response(['status' => 'Group does not exist'], 403);
    }

    public static function edit($user, $data) {
        $group = Group::find($data['group_id']);
        if (is_object($group)) {
            if ($group->users->contains($user->id)) {

                $currentRelation = $group->users()
                                 ->where('users.id', '=', $user->id)
                                 ->first();
                if ($currentRelation->pivot->admin) {
                    // Edit

                    return response(['status' => 'Edit successfull'], 200);
                }
                return response(['status' => 'User is not admin'], 403);
            }
            return response(['status' => 'User does not belong to group'], 403);
        }
        return response(['status' => 'Group does not exist'], 403);
    }
}