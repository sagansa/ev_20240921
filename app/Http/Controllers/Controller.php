<?php

namespace App\Http\Controllers;

use App\Models\ChargerLocation;
use App\Models\PlnChargerLocation;

abstract class Controller
{
    public function showMap()
{
    $locations = ChargerLocation::select('latitude', 'longitude')->get();

    $chargerLocations = PlnChargerLocation::select('latitude', 'longitude')->get();

    return view('map-view', ['locations' => $locations, 'chargerLocations' => $chargerLocations]);
}
}
