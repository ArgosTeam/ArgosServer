<?php
namespace App\Classes;


use App\Models\Group;
use App\Models\User;
use App\Models\Hashtag;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;
use App\Models\Location;
use App\Classes\PhotoFunctions;
use Illuminate\Support\Facades\Storage;

class GroupFunctions
{
    public static function add($user, $request) {
        $group = Group::where('name', '=', $request->input('name'))
               ->first();
        if (is_object($group)) {
            return response('This group name already exists', 404);
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

        } else {
            return response('User not found', 404);
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
        return response(['status' => 'Group does not exist or invite already exists'], 404);
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
                    // TODO : Add InvitedGroup Notification
                    }
                    
                }

                return response(['status' => 'Invites sent'], 200);
            }
            return response(['status' => 'Access refused'], 404);
        }
        return response(['status' => 'Group does not exist'], 404);
    }

    public static function acceptInvite($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            $group->users()->updateExistingPivot($user->id, [
                'status' => 'acccepted'
            ]);
            return response(['status' => 'Invite accepted'], 200);
        }
        return response(['status' => 'Group does not exist'], 404);
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
            return response(['status' => 'Access refused, need to be admin, or group does not exist'], 404);
        }
    }

    public static function infos($user, $group_id) {
        $group = Group::find($group_id);
        if (is_object($group)) {
            
            $data = [];
            $profile_pic = $group->profile_pic()->first();
            $profile_pic_path = null;
            // Get signed url from s3
            if (is_object($profile_pic)) {
                $s3 = Storage::disk('s3');
                $client = $s3->getDriver()->getAdapter()->getClient();
                $expiry = "+10 minutes";
                
                $command = $client->getCommand('GetObject', [
                    'Bucket' => env('S3_BUCKET'),
                    'Key'    => $profile_pic->path,
                ]);
                $request = $client->createPresignedRequest($command, $expiry);
                $profile_pic_path = '' . $request->getUri() . '';
            }
            
            $data['id'] = $group_id;
            $data['profile_pic'] = $profile_pic_path;
            $data['name'] = $group->name;
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
            
            return response($data, 200);
        }
        return response('Group does not exist', 404);
    }

    public static function profile_pic($user, $encode, $group_id) {
        $group = $user->groups()->where('groups.id', '=', $group_id)->first();
        if (!is_object($group)) {
            return response([ 'error' => 'access refused'], 404);
        }

        Log::info(print_r($group->pivot, true));
        if (!$group->pivot->admin) {
            return response(['error' => 'You need to be admin'], 404);
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
            return response(['status' => 'This photo does not belong to you'], 404);
        }

        foreach ($groups as $group) {
            if (!is_object($group)) {
                return response(['status' => 'Group does not exists'], 404);
            }
            if (!$group->users->contains($user->id)) {
                return response(['status' => 'Access to group denied'], 404);
            }

            if ($group->photos->contains($photo->id)) {
                return response('Photo already linked to group', 404);
            }
            $group->photos()->attach($photo->id);
        }

        return response(['status' => 'Photo linked to group'], 200);
    }

    public static function photos($user, $group_id) {
        $group = Group::find($group_id);
        if (!is_object($group)) {
            return response(['status' => 'Group does not exists'], 404);
        }

        $response = [];
        foreach ($group->photos as $photo) {
            // Get signed url from s3
            $s3 = Storage::disk('s3');
            $client = $s3->getDriver()->getAdapter()->getClient();
            $expiry = "+10 minutes";
            
            $command = $client->getCommand('GetObject', [
                'Bucket' => env('S3_BUCKET'),
                'Key'    => "avatar-" . $photo->path,
            ]);
            $request = $client->createPresignedRequest($command, $expiry);
            
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
}