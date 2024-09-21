<?php

namespace App\Http\Controllers;

use App\Models\ChargerLocation;

abstract class Controller
{
    public function showMap()
{
    $locations = ChargerLocation::select('latitude', 'longitude')->get();

    return view('map-view', ['locations' => $locations]);
}
}
