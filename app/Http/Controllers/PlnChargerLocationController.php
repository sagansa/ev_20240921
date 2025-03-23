<?php

namespace App\Http\Controllers;

use App\Models\PlnChargerLocation;
use Illuminate\Http\Request;

class PlnChargerLocationController extends Controller
{
    public function index()
    {
        // $locations = PlnChargerLocation::all();
        return view('layouts.ev.home');
    }

    public function map()
    {
        $plnLocations = PlnChargerLocation::all();
        return view('layouts.ev.pln-map', compact('plnLocations'));
    }
}
