<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;

class SearchFunctions {

    public static function  getRelatives($user, $search) {
        $users = json_decode(DB::table('users')
                             ->where('firstname', 'like', '%' . $search . '%')
                             ->orWhere('lastname', 'like', '%' . $search . '%')
                             ->get(), true);
        $groups =  json_decode(DB::table('groups')
                               ->where('name', 'like', '%' . $search . '%')
                               ->get(), true);
        
        \Illuminate\Support\Facades\Log::info(print_r($users, true));
        $data = [];
        foreach ($users as $user) {
            $newEntry = [];
            $newEntry['id'] = $user['id'];
            $newEntry['url'] = null;
            $newEntry['name'] = $user['firstName'] . ' ' . $user['lastName'];
            $newEntry['type'] = 'user';
            $data[] = $newEntry;
        }
        foreach ($groups as $group) {
            $newEntry = [];
            $newEntry['id'] = $group['id'];
            $newEntry['url'] = null;
            $newEntry['name'] = $group['name'];
            $newEntry['type'] = 'group';
            $data[] = $newEntry;
        }
        \Illuminate\Support\Facades\Log::info(print_r($data, true));
        return (json_encode($data));
    }
}