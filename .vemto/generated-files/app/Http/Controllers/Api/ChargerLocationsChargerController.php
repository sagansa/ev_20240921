<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChargerLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChargerResource;
use App\Http\Resources\ChargerCollection;

class ChargerLocationsChargerController extends Controller
{
    public function index(
        Request $request,
        ChargerLocation $chargerLocation
    ): ChargerCollection {
        $search = $request->get('search', '');

        $chargers = $this->getSearchQuery($search, $chargerLocation)
            ->latest()
            ->paginate();

        return new ChargerCollection($chargers);
    }

    public function store(
        Request $request,
        ChargerLocation $chargerLocation
    ): ChargerResource {
        $validated = $request->validate([
            'current_charger_id' => ['required'],
            'type_charger_id' => ['required'],
            'power_charger_id' => ['required'],
            'unit' => ['required'],
        ]);

        $charger = $chargerLocation->chargers()->create($validated);

        return new ChargerResource($charger);
    }

    public function getSearchQuery(
        string $search,
        ChargerLocation $chargerLocation
    ) {
        return $chargerLocation
            ->chargers()
            ->where('created_at', 'like', "%{$search}%");
    }
}
