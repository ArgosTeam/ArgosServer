<?php

namespace App\Http\Controllers;

use App\Classes\LocationFunctions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;

class LocationController extends Controller
{
    public function geocoding(Request $request) {
        $address = $request->input('address');
        $geocode = app('geocoder')->geocode('address')->get();
        Log::info('GEOCODE : ' . print_r($geocode));
        return response(['status' => 'ok'], 200);
    }

}
