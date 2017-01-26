<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Input;

class SearchFunctions {

    public static function  getContacts($user) {
        $search = \Illuminate\Support\Facades\Input::get('name_begin');
        $search = \Illuminate\Support\Facades\Input::get('known_only');


        // TODO: use known_only to seek in friends tab
        $users = json_decode(DB::table('users')
                             ->where('firstname', 'like', '%' . $search . '%')
                             ->orWhere('lastname', 'like', '%' . $search . '%')
                             ->get(), true);
        $groups =  json_decode(DB::table('groups')
                               ->where('name', 'like', '%' . $search . '%')
                               ->get(), true);
        $data = [];
        foreach ($users as $user) {
            $newEntry = [];
            $newEntry['id'] = $user['id'];
            $newEntry['url'] = null;
            $newEntry['name'] = $user['firstName'] . ' ' . $user['lastName'];
            $newEntry['type'] = 'user';
            $newEntry['pending'] = false;
            $data[] = $newEntry;
        }
        foreach ($groups as $group) {
            $newEntry = [];
            $newEntry['id'] = $group['id'];
            $newEntry['url'] = null;
            $newEntry['name'] = $group['name'];
            $newEntry['type'] = 'group';
            $newEntry['pending'] = false;
            $data[] = $newEntry;
        }
        return (json_encode($data));
    }
}