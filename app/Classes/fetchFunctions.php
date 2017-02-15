<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Event;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Location;
use App\Models\Photo;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

//http://gis.stackexchange.com/questions/31628/find-points-within-a-distance-using-mysql
class fetchFunctions
{
    
    public static function fetch($data) {
        
        $poly[0] = explode(",",str_replace(["LatLng(", ")"], " ", $data["farLeft"]));
        $poly[1] = explode(",",str_replace(["LatLng(", ")"], " ", $data["farRight"]));
        $poly[2] = explode(",",str_replace(["LatLng(", ")"], " ", $data["nearLeft"]));
        $poly[3] = explode(",",str_replace(["LatLng(", ")"], " ", $data["nearRight"]));
        $poly[4] = explode(",",str_replace(["LatLng(", ")"], " ", $data["farLeft"]));


        $cells = [
            [
                [],[],[],[]
            ],[
                [],[],[],[]
            ],[
                [],[],[],[]
            ],[
                [],[],[],[]
            ],[
                [],[],[],[]
            ],[
                [],[],[],[]
            ]
        ];

        $width = ((float)$poly[0][0] - (float)$poly[3][0])/4;
        $height = ((float)$poly[3][1] - (float)$poly[0][1])/8;


        for($i = 0; $i < 8; $i++){
            for($a = 0; $a < 4; $a++){

                $leftTop = (((float)$poly[2][0]) + ($height * ($i + 1))) . " " . (((float)$poly[2][1]) + ($width * $a));
                $rightTop = (((float)$poly[2][0]) + ($height * ($i + 1))) . " " . (((float)$poly[2][1]) + ($width * ($a + 1)));
                $rightBttm = (((float)$poly[2][0]) + ($height * ($i))) . " " . (((float)$poly[2][1]) + ($width * ($a + 1)));
                $leftBttm = (((float)$poly[2][0]) + ($height * $i)) . " " . (((float)$poly[2][1]) + ($width * ($a)));

                $cells[$i][$a] = [$leftTop, $rightTop, $rightBttm, $leftBttm, $leftTop];
            }
        }

        $filter = array_key_exists('filter', $data) ? $data['filter'] : [];
        $filter['users'] = array_key_exists('users', $filter)
                         ? $filter['users']
                         : [];
        $filter['groups'] = array_key_exists('groups', $filter)
                         ? $filter['groups']
                         : [];
        $filter['hashtags'] = array_key_exists('hashtags', $filter)
                         ? $filter['hashtags']
                         : [];
        $results = fetchFunctions::fetchAll($cells, $filter);
        return ($results);
    }


    // public static function fetchAll($cells, $filter)
    // {
    //     $results = [];

    //     foreach ($cells AS $row) {
    //         foreach ($row AS $col) {
    //             $poly = $col;
    //             $locations = Location::query()
    //                        ->whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))")
    //                        ->latest()
    //                        ->limit(10)
    //                        ->get();

    //             $main = true;
    //             foreach ($locations as $index => $location) {

    //                 Log::info('Location');
    //                 /*
    //                 ** Photo fetch
    //                 */
    //                 // if (is_object($location->photo()->first())) {
    //                 //     Log::info('photo');
    //                 //     $photo = $location->photo()->first();
    //                 //     // Get signed url from s3
    //                 //     $s3 = Storage::disk('s3');
    //                 //     $client = $s3->getDriver()->getAdapter()->getClient();
    //                 //     $expiry = "+10 minutes";
                        
    //                 //     $command = $client->getCommand('GetObject', [
    //                 //         'Bucket' => env('S3_BUCKET'),
    //                 //         'Key'    => "avatar-" . $photo->path,
    //                 //     ]);
    //                 //     $request = $client->createPresignedRequest($command, $expiry);
                        
    //                 //     if ($main) {
    //                 //         $results[] = [
    //                 //             'type' => 'photo',
    //                 //             'id' => $photo->id,
    //                 //             'name' => $photo->name,
    //                 //             'path' => '' . $request->getUri() . '',
    //                 //             'lat' => $location->lat,
    //                 //             'lng' => $location->lng,
    //                 //             'photos' => []
    //                 //         ];
    //                 //     } else {
    //                 //         $results[0]['photos'][] = [
    //                 //             'id' => $photo->id,
    //                 //             'name' => $photo->name,
    //                 //             'path' => '' . $request->getUri() . '',
    //                 //             'lat' => $location->lat,
    //                 //             'lng' => $location->lng,
    //                 //         ];
    //                 //     }
    //                 //     $main = false;
    //                 // }

    //                 // // Group fetch
    //                 // if (is_object($location->group()->first())) {
                        
    //                 // }

    //                 // //  Event fetch
    //                 // if (is_object($location->group()->first())) {
                        
    //                 // }
    //             }
                
    //         }
    //     }


    //     return $results;

    // }

    public static function fetchAll($cells, $filter) {
        $results = [];

        $filter_group = empty($filter['groups']) ? false : true;
        $filter_user = empty($filter['users']) ? false : true;
        foreach ($cells AS $row) {
            foreach ($row AS $col) {
                $poly = $col;
                
                /*
                ** Base of photos request, add conditions on locations to be in the screen
                ** Group and User are 2 separated filters, for more clarity
                ** 2 variables photos_users and photos_groups are used
                */
                $query_locations_photos_users = Location::whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))");
                
                $query_locations_photos_groups = clone $query_locations_photos_users;

                /*
                ** Base of groups request
                */
                $query_locations_groups = clone $query_locations_photos_users;
                
                /*
                ** Add query filters dependencies
                */
                fetchFunctions::addJoinPhotoUserFilter($query_locations_photos_users, $filter['users'], $filter['hashtags']);
                fetchFunctions::addJoinPhotoGroupFilter($query_locations_photos_groups, $filter['groups'], $filter['hashtags']);
                fetchFunctions::addJoinGroupFilter($query_locations_groups, $filter['groups']);
           
                /*
                ** Get Users picture, then flush ids to exclude them for next request
                ** If users filter not applied, get all latest photos_users
                */
                $locations_photos_users = $query_locations_photos_users
                                        ->latest()
                                        ->limit(10)
                                        ->get();

                
                // $exclude_ids = is_object($photos_users)
                //              ? $photos_users->pluck('id')
                //              : [];
                
                /*
                ** Get Groups pictures
                ** Same here, if groups filter not applied,
                ** will get 10 more latest records
                */
                $locations_photos_groups = $query_locations_photos_groups
                                         ->latest()
                                         ->limit(10)
                                         ->get();

                /*
                ** Get Groups -- TODO : check rights to display info
                */
                $locations_groups = $query_locations_groups
                        ->latest()
                        ->get();
                
                $locations = $locations_photos_users->merge($locations_photos_groups)
                           ->merge($locations_groups)
                           ->sortBy('created_at');

                $main = true;
                foreach ($locations as $index => $location) {

                    Log::info('Location');
                    /*
                    ** Photo fetch
                    */
                    if (is_object($location->photo()->first())) {
                        $photo = $location->photo()->first();
                        // Get signed url from s3
                        $s3 = Storage::disk('s3');
                        $client = $s3->getDriver()->getAdapter()->getClient();
                        $expiry = "+10 minutes";
                        
                        $command = $client->getCommand('GetObject', [
                            'Bucket' => env('S3_BUCKET'),
                            'Key'    => "avatar-" . $photo->path,
                        ]);
                        $request = $client->createPresignedRequest($command, $expiry);
                        
                        if ($main) {
                            $results[] = [
                                'type' => 'photo',
                                'id' => $photo->id,
                                'name' => $photo->name,
                                'path' => '' . $request->getUri() . '',
                                'lat' => $location->lat,
                                'lng' => $location->lng,
                                'photos' => []
                            ];
                        } else {
                            $results[0]['photos'][] = [
                                'id' => $photo->id,
                                'name' => $photo->name,
                                'path' => '' . $request->getUri() . '',
                                'lat' => $location->lat,
                                'lng' => $location->lng,
                            ];
                        }
                        $main = false;
                    }

                    // // Group fetch
                    // if (is_object($location->group()->first())) {
                        
                    // }

                    // //  Event fetch
                    // if (is_object($location->group()->first())) {
                        
                    // }
                }

                

            }
        }

        Log::info(print_r($results, true));

        return $results;

    }

    /*
    ** Generic Manipulations of queries on Location Model only
    */
    private static function addJoinPhotoUserFilter($query, $users_id, $hashtags) {
        $query->whereHas('photo', function ($joinQuery) use ($users_id, $hashtags) {
            $joinQuery->whereHas('users', function ($joinQuery) use ($users_id) {
                if (!empty($users_id)) {
                    $joinQuery->whereIn('users.id', $users_id);
                }
            });
            if (!empty($hashtags)) {
                $joinQuery->whereHas('hashtags', function ($joinQuery) use ($hashtags) {
                    $joinQuery->whereIn('hashtags.name', $hashtags);
                });
            }
        });
    }

    private static function addJoinPhotoGroupFilter($query, $groups_id, $hashtags) {
        $query->whereHas('photo', function ($joinQuery) use ($groups_id, $hashtags) {
            $joinQuery->whereHas('groups', function ($joinQuery) use ($groups_id) {
                if (!empty($groups_id)) {
                    $joinQuery->whereIn('groups.id', $groups_id);
                }
            });
            if (!empty($hashtags)) {
                $joinQuery->whereHas('hashtags', function ($joinQuery) use ($hashtags) {
                    $joinQuery->whereIn('hashtags.name', $hashtags);
                });
            }
        });
    }

    private static function addJoinGroupFilter($query, $groups_id) {
        $query->whereHas('group', function ($query) use ($groups_id) {
            if (!empty($groups_id)) {
                $query->whereIn('groups.id', $groups_id);
            }
        });
    }
    
}
