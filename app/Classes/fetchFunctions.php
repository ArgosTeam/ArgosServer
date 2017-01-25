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

/**
 * Created by PhpStorm.
 * User: Neville
 * Date: 26/09/2016
 * Time: 6:56 AM
 */

//http://gis.stackexchange.com/questions/31628/find-points-within-a-distance-using-mysql
class fetchFunctions
{
    
    public function fetch(){

        $data = \Illuminate\Support\Facades\Input::get();
        
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

        $width = ($poly[0][0] - $poly[3][0])/4;
        $height = ($poly[3][1] - $poly[0][1])/8;


        for($i = 0; $i < 8; $i++){
            for($a = 0; $a < 4; $a++){

                $leftTop = (($poly[2][0]) + ($height * ($i + 1))) . " " . (($poly[2][1]) + ($width * $a));
                $rightTop = (($poly[2][0]) + ($height * ($i + 1))) . " " . (($poly[2][1]) + ($width * ($a + 1)));
                $rightBttm = (($poly[2][0]) + ($height * ($i))) . " " . (($poly[2][1]) + ($width * ($a + 1)));
                $leftBttm = (($poly[2][0]) + ($height * $i)) . " " . (($poly[2][1]) + ($width * ($a)));

                $cells[$i][$a] = [$leftTop, $rightTop, $rightBttm, $leftBttm, $leftTop];
            }
        }

//        SELECT  * FROM `photos` WHERE ST_CONTAINS( PolygonFromText('POLYGON((53.36854460722273 -6.272425912320615, 53.36854460722273 -6.2569767236709595, 53.35276852530885 -6.2569767236709595, 53.35276852530885 -6.272425912320615, 53.36854460722273 -6.272425912320615))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')));


        $results = [];
        $results["photos"] = $this->fetchPhotos($cells);
        $results["events"] = $this->fetchEvents($cells);
        $results["locations"] = $this->fetchLocations($cells);

        return ($results);

    }


    private function fetchPhotos($cells)
    {

        $results = [];

        foreach ($cells AS $row) {
            foreach ($row AS $col) {

                $poly = $col;


                $photos = Photo::query();
                $photos = $photos->whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))");
                $photos = $photos->latest()->first();


                if(is_object($photos)) {
                    $results[] = [
                        "id" => $photos->id,
                        "name" => $photos->name,
                        "path" => env('S3_URL') . env('S3_BUCKET') . "/avatar-" . $photos->path,
                        "lat" => $photos->lat,
                        "lng" => $photos->lng,
                    ];
                }

            }
        }


        return $results;

    }

    private function fetchEvents($cells)
    {

        $results = [];

        foreach ($cells AS $row) {
            foreach ($row AS $col) {

                $poly = $col;

                $events = Event::query();
                $events = $events->whereHas('location', function ($q) use ($poly) {
                    $q->whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))");
                });
                $events = $events->latest()->first();

                if(is_object($events)) {
                    $results[] = [
                        "id" => $events->id,
                        "name" => $events->name,
                        "lat" => $events->lat,
                        "lng" => $events->lng,
                    ];
                }



            }
        }

        return $results;
    }

    private function fetchLocations($cells)
    {


        $results = [];

        foreach ($cells AS $row) {
            foreach ($row AS $col) {

                $poly = $col;

                $locations = Location::query();
                $locations = $locations->whereRaw("ST_CONTAINS(PolygonFromText('POLYGON((" . implode(',', $poly) . "))'), GeomFromText(CONCAT('Point(',`lat`, ' ', `lng`,')')))");
                $locations = $locations->latest()->first();

                if(is_object($locations)) {
                    $results[] = [
                        "id" => $locations->id,
                        "name" => $locations->name,
                        "lat" => $locations->lat,
                        "lng" => $locations->lng,
                    ];
                }


            }
        }

        return $results;

    }
}
