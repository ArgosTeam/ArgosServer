<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class SearchFunctions {

    public static function  getContacts($user, $nameBegin, $knownOnly) {
        
        
        // TODO: use known_only to seek in friends tab
        $users = User::leftJoin('user_users', 'users.id', '=', 'user_users.user_id')
               ->where('users.firstname', 'like', $nameBegin . '%')
               ->orWhere('users.lastname', 'like', $nameBegin . '%')
               ->get();
        Log::info(print_r($users, true));
        $groups =  Group::where('name', 'like', $nameBegin . '%')
                ->get();
        $data = [];
        foreach ($users as $user) {
            $newEntry = [];
            $newEntry['id'] = $user->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $user->firstName . ' ' . $user->lastName;
            $newEntry['type'] = 'user';
            $newEntry['pending'] = false;//$user->active == null ? false : $user->active;
            $data[] = $newEntry;
        }
        foreach ($groups as $group) {
            $newEntry = [];
            $newEntry['id'] = $group->id;
            $newEntry['url'] = null;
            $newEntry['name'] = $group->name;
            $newEntry['type'] = 'group';
            $newEntry['pending'] = false;
            $data[] = $newEntry;
        }
        return (json_encode($data));
    }
}