<?php

namespace App\Http\Controllers;

use App\Models\ChargerLocation;
use Illuminate\Http\Request;

class ChargerLocationController extends Controller
{
    public function index()
    {
        $chargerLocations = ChargerLocation::all();
        // return view('landing-page', compact('contact', 'products'));
        return view('layouts/ev/location', compact('chargerLocations'));
    }
}
