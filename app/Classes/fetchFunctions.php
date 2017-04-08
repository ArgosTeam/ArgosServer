<?php
namespace App\Classes;
use App\Http\Requests\SubmitUploadPhoto;
use App\Models\Event;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Location;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use App\Classes\PhotoFunctions;

//http://gis.stackexchange.com/questions/31628/find-points-within-a-distance-using-mysql
class fetchFunctions
{
    
    public static function fetch($data) {

        // pol
        
        $poly[0] = explode(",", $data["farLeft"]);
        $poly[1] = explode(",", $data["farRight"]);
        $poly[2] = explode(",", $data["nearLeft"]);
        $poly[3] = explode(",", $data["nearRight"]);

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

        /*
        ** Calculation of width and height distances
        */
        // $width = sqrt(
        //     pow(
        //         ((float)$poly[0][0] - (float)$poly[1][0]),
        //         2)
        //     + pow(
        //         ((float)$poly[0][1] - (float)$poly[1][1]),
        //         2));
        // $height = sqrt(
        //     pow(
        //         ((float)$poly[0][0] - (float)$poly[2][0]),
        //         2)
        //     + pow(
        //         ((float)$poly[0][1] - (float)$poly[2][1]),
        //         2));


        /*
        ** Let (A,x,y) x,y being vectors -> x(1, 0), y(0, 1) -> simulate lat/lng
        ** If farleft point A is A(i, j), Let M(q,w)
        ** AM = (q - i)x + (w - j)y
        ** Example with horizontal split :
        **   If M = farRight(k, l)
        **   AM = (k - i)x + (l - j)y
        **   So vector AM = Ax + By, A = k - i, B = j - l
        ** Then multiply vector by associated conf in order to split the screen
        */
        $farLeftY = (float)$poly[0][1];
        $farLeftX = (float)$poly[0][0];
        $splitV = 8;
        $splitH = 4;

        /*
        ** Set up vectors 
        */
        $vRightX = (float)$poly[1][0] - $farLeftX;
        $vRightY = -$farLeftY + (float)$poly[1][1];
        $vDownX = (float)$poly[2][0] - $farLeftX;
        $vDownY = -$farLeftY + (float)$poly[2][1];
        
        for ($v = 0; $v < $splitV; $v++) {
            for ($h = 0; $h < $splitH; $h++) {

                // Create string as model : "lat lng" for sql query 
                $leftTop = (float)($farLeftX + $vRightX * (float)($h / $splitH) . ' ' . $farLeftY + $vDownY * (float)((float)$v / (float)$splitV));
                $rightTop = (float)($farLeftX + $vRightX * (float)((float)($h + 1) / $splitH) . ' ' . $farLeftY + $vDownY * (float)((float)$v / (float)$splitV));
                $rightBttm = (float)($farLeftX + $vRightX * (float)((float)($h + 1) / $splitH) . ' ' . $farLeftY + $vDownY * (float)((float)((float)$v + 1) / (float)$splitV));
                $leftBttm = (float)($farLeftX + $vRightX * (float)((float)$h / (float)$splitH) . ' ' . $farLeftY + $vDownY * (float)((float)((float)$v + 1) / (float)$splitV));
            }

            $cells[$v][$h] = [$leftTop, $rightTop, $rightBttm, $leftBttm, $leftTop]; // Double leftTop for sql polygon request
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
        $mode = $data['mode'];
        $results = fetchFunctions::fetchAll($cells, $filter, $mode);
        return ($results);
    }

    public static function fetchAll($cells, $filter, $mode) {
        $results = [];
        $user = Auth::user();

        $index = -1;
        foreach ($cells AS $row) {
            foreach ($row AS $col) {
                $poly = $col;

                $query_base_locations = Location::whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))");
                $locations_photos_users = collect();
                $locations_groups = collect();
                $locations_events = collect();
                if ($mode == 'photo'
                    || $mode == 'all') {
                    
                    /*
                    ** Base of photos request, add conditions on locations to be in the screen
                    ** Group and User are 2 separated filters, for more clarity
                    */
                    
                    $query_locations_photos_users = clone $query_base_locations;

                    /*
                    ** Add query filters dependencies
                    */
                    fetchFunctions::addJoinPhotoUserFilter($query_locations_photos_users, $filter['users'], $filter['hashtags']);

                    /*
                    ** Get Users picture
                    ** If users filter not applied, get all latest photos_users
                    */
                    $locations_photos_users = $query_locations_photos_users
                                        ->latest()
                                        ->limit(15)
                                        ->get();
                    
                }

                if ($mode == 'group'
                    || $mode == 'all') {
                    /*
                    ** Base of groups request
                    */
                    $query_locations_groups = clone $query_base_locations;
                    fetchFunctions::addJoinGroupFilter($query_locations_groups, $filter['groups']);
                    
                    /*
                    ** Get Groups -- TODO : check rights to display info
                    */
                    $locations_groups = $query_locations_groups
                                      ->latest()
                                      ->limit(1)
                                      ->get();
                }

                if ($mode == 'event'
                    || $mode == 'all') {
                    $query_locations_events = clone $query_base_locations;
                    fetchFunctions::addJoinEventFilter($query_locations_events);

                    $locations_events = $query_locations_events
                                      ->latest()
                                      ->limit(1)
                                      ->get();
                }
           
                $locations = $locations_photos_users
                           ->merge($locations_groups)
                           ->merge($locations_events)
                           ->sortBy('created_at');

                $main = true;
                foreach ($locations as $location) {

                    /*
                    ** Photo fetch
                    */
                    if (is_object($location->photo()->first())) {
                        $photo = $location->photo()->first();

                        $request = PhotoFunctions::getUrl($photo, 'avatar');
                        
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
                            $index += 1;
                        } else {
                            $results[$index]['photos'][] = [
                                'id' => $photo->id,
                                'name' => $photo->name,
                                'path' => '' . $request->getUri() . '',
                                'lat' => $location->lat,
                                'lng' => $location->lng,
                            ];
                        }
                        $main = false;
                    }
                    
                    /*
                    ** Group fetch
                    */
                    if (is_object($location->group()->first())) {
                        $group = $location->group()->first();
                        /*
                        ** If the item selected in the grid is a photo
                        ** Continue to try other locations at the same point
                        ** To fill Array Photo on the first selected photo
                        */
                        if (!$main) {
                            continue ;
                        }

                        $profile_pic = $group->profile_pic()->first();
                        $profile_pic_path = null;
            
                        if (is_object($profile_pic)) {
                            $request = PhotoFunctions::getUrl($profile_pic, 'avatar');
                            $profile_pic_path = '' . $request->getUri() . '';
                        }
                        
                        // If a group is selected, break the loop
                        $results[] = [
                            'type' => 'group',
                            'id' => $group->id,
                            'name' => $group->name,
                            'path' => $profile_pic_path,
                            'lat' => $group->location->lat,
                            'lng' => $group->location->lng
                        ];
                        $index += 1;
                        break ;
                    }

                    /*
                    ** Event fetch
                    */
                    if (is_object($location->event()->first())) {
                        $event = $location->event()->first();

                        /*
                        ** If the item selected in the grid is a photo
                        ** Continue to try other locations at the same point
                        ** To fill Array Photo on the first selected photo
                        */

                        if (!$event->public && !$event->users->contains($user->id)) {
                            continue ;
                        }
                        if (!$main) {
                            continue ;
                        }

                        $profile_pic = $event->profile_pic()->first();
                        $profile_pic_path = null;
            
                        if (is_object($profile_pic)) {
                            $request = PhotoFunctions::getUrl($profile_pic, 'avatar');
                            $profile_pic_path = '' . $request->getUri() . '';
                        }
                        
                        // If an event is selected, break the loop
                        $results[] = [
                            'type' => 'event',
                            'id' => $event->id,
                            'name' => $event->name,
                            'path' => $profile_pic_path,
                            'lat' => $event->location->lat,
                            'lng' => $event->location->lng
                        ];
                        $index += 1;
                        break ;
                    }
                }
            }
        }
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

    private static function addJoinEventFilter($query) {
        $query->whereHas('event');
    }
    
}
