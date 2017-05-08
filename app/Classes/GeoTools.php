<?php
namespace App\Classes;


class GeoTools
{
    /*
    ** Calculation of spherical (non-elipsoid) between 2 lat/lng
    ** Medium accuracy, medium perf
    */ 
    public static function haversine($coord1, $coord2) {
        // Earth radius in meters
        $r = 6378137;

        $l1 = $coord1[0] * (M_PI / 180);
        $l2 = $coord2[0] * (M_PI / 180);

        $delta1 = ($coord2[0] - $coord1[0]) * (M_PI / 180);
        $delta2 = ($coord2[1] - $coord1[1]) * (M_PI / 180);

        $a = sin($delta1 / 2) * sin($delta1 / 2)
           + cos($l1) * cos($l2)
           * sin($delta2 / 2) * sin($delta2 / 2);

        $c = 2 * atan2(sqrt(a), sqrt(1-a));

        $distance = $r * $c;

        return $distance;
    }
    
}