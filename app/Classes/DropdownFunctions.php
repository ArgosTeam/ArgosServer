<?php
namespace App\Classes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Group;

class DropdownFunctions{

    public static function generalSelect()
    {
        if (array_key_exists("q", $_GET)) {
            $term = $_GET["q"];
            $rawTerm = $term;

            if (trim($term) == "") {
                $term = "%";
            } else {
                $term = "%" . $term . "%";
            }
        } else {
            $term = "%";
        }

        $users = DB::table('users')
            ->select(DB::raw("CONCAT(firstName, ' ', lastName) AS name, CONCAT('USER-', id) AS idt"))
            ->whereRaw('CONCAT(firstName, " ", lastName) LIKE ' . "'" . $term . "'" . ' ')
            // ->whereNull('users.deleted_at')
            ->limit(10)
            ->get();

        $groups = DB::table('groups')
            ->select(DB::raw("name, CONCAT('GROUP-', id) AS idt"))
            ->where('name', 'LIKE', $term)
            // ->whereNull('groups.deleted_at')
            ->limit(10)
            ->get();

        // $users = $users->unionAll($groups);
        // $users->orderBy('name', 'desc');
        // $users->limit(25);
        // $users = $users->get();


        $results = ["results" => []];

        if (sizeof($users) != 0) {
            foreach ($users as $data) {
                $tempArray = [];
                $tempArray["id"] = $data->idt;
                $tempArray["text"] = $data->name;

                // TO ADD
                // $tempArray["profile_url"] = $data->profile_url;
                $tempArray["profile_url"] = "https://organicthemes.com/demo/profile/files/2012/12/profile_img.png";
                $results["results"][] = $tempArray;
            }
        }
        else {
            return (["status" => "no match"]);
        }

        return ($results);
    }

}