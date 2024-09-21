<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChargerLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiscountHomeChargingResource;
use App\Http\Resources\DiscountHomeChargingCollection;

class ChargerLocationsDiscountHomeChargingController extends Controller
{
    public function index(
        Request $request,
        ChargerLocation $chargerLocation
    ): DiscountHomeChargingCollection {
        $search = $request->get('search', '');

        $discountHomeChargings = $this->getSearchQuery(
            $search,
            $chargerLocation
        )
            ->latest()
            ->paginate();

        return new DiscountHomeChargingCollection($discountHomeChargings);
    }

    public function store(
        Request $request,
        ChargerLocation $chargerLocation
    ): DiscountHomeChargingResource {
        $validated = $request->validate([
            'month' => ['required', 'date'],
            'total_kwh' => ['required'],
            'discount_kwh' => ['required'],
            'discount_total' => ['required'],
            'user_id' => ['required'],
        ]);

        $discountHomeCharging = $chargerLocation
            ->discountHomeChargings()
            ->create($validated);

        return new DiscountHomeChargingResource($discountHomeCharging);
    }

    public function getSearchQuery(
        string $search,
        ChargerLocation $chargerLocation
    ) {
        return $chargerLocation
            ->discountHomeChargings()
            ->where('charger_location_id', 'like', "%{$search}%");
    }
}
