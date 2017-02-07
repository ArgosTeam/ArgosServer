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
        
        $poly[0] = explode(",",str_replace(["lat/lng: (", ")"], " ", $data["farLeft"]));
        $poly[1] = explode(",",str_replace(["lat/lng: (", ")"], " ", $data["farRight"]));
        $poly[2] = explode(",",str_replace(["lat/lng: (", ")"], " ", $data["nearLeft"]));
        $poly[3] = explode(",",str_replace(["lat/lng: (", ")"], " ", $data["nearRight"]));
        $poly[4] = explode(",",str_replace(["lat/lng: (", ")"], " ", $data["farLeft"]));


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

        $results = fetchFunctions::fetchAll($cells);
        return ($results);
    }


    public static function fetchAll($cells)
    {

        $results = [];

        foreach ($cells AS $row) {
            foreach ($row AS $col) {

                $poly = $col;
                $locations = Location::query()
                           ->whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))")
                           ->latest()
                           ->limit(10)
                           ->get();
                $main = true;
                foreach ($locations as $index => $location) {
                    if (is_object($location->photo()->first())) {
                        Log::info('photo');
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
                }
                
            }
        }


        return $results;

    }
    
}
