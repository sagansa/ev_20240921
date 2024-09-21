<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChargerLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeResource;
use App\Http\Resources\ChargeCollection;

class ChargerLocationsChargeController extends Controller
{
    public function index(
        Request $request,
        ChargerLocation $chargerLocation
    ): ChargeCollection {
        $search = $request->get('search', '');

        $charges = $this->getSearchQuery($search, $chargerLocation)
            ->latest()
            ->paginate();

        return new ChargeCollection($charges);
    }

    public function store(
        Request $request,
        ChargerLocation $chargerLocation
    ): ChargeResource {
        $validated = $request->validate([
            'vehicle_id' => ['required'],
            'date' => ['required', 'date'],
            'km_now' => ['required'],
            'km_before' => ['required'],
            'start_charging_now' => ['required'],
            'finish_charging_now' => ['required'],
            'finish_charging_before' => ['required'],
            'parking' => ['required'],
            'kWh' => ['required'],
            'street_lighting_tax' => ['required'],
            'value_added_tax' => ['required'],
            'admin_cost' => ['required'],
            'total_cost' => ['required'],
            'image' => ['nullable', 'image', 'max:1024'],
            'user_id' => ['required'],
            'charger_id' => ['required'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('public');
        }

        $charge = $chargerLocation->charges()->create($validated);

        return new ChargeResource($charge);
    }

    public function getSearchQuery(
        string $search,
        ChargerLocation $chargerLocation
    ) {
        return $chargerLocation
            ->charges()
            ->where('date', 'like', "%{$search}%");
    }
}
