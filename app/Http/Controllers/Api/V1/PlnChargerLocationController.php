<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\PlnChargerLocation;
use Illuminate\Http\Request;

class PlnChargerLocationController extends Controller
{
    /**
     * Display a listing of PLN charging locations.
     */
    public function index(Request $request)
    {
        $query = PlnChargerLocation::with([
            'provider',
            'province',
            'locationCategory',
            'plnChargerLocationDetails.chargerCategory',
            'plnChargerLocationDetails.merkCharger',
            'plnChargerLocationDetails.chargingType',
        ]);

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('cluster_island_id')) {
            $query->where('cluster_island_id', $request->cluster_island_id);
        }

        if ($request->filled('location_category_id')) {
            $query->where('location_category_id', $request->location_category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $plnLocations = $query->get()->map(fn (PlnChargerLocation $location) => $this->transformLocation($location));

        return response()->json([
            'success' => true,
            'message' => 'PLN charging locations retrieved successfully',
            'data' => $plnLocations,
        ]);
    }

    /**
     * Transform the PLN location into a consistent API response structure.
     */
    private function transformLocation(PlnChargerLocation $location): array
    {
        return [
            'id' => (string) $location->id,
            'name' => $location->name,
            // 'provider_id' => $location->provider_id ? (string) $location->provider_id : null,
            'provider' => $location->provider ? [
                'image' => $location->provider->image,
                'name' => $location->provider->name,
            ] : null,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'address' => $location->address ?? '',
            'province' => $location->province ? [
                'name' => $location->province->name,
            ] : null,
            // 'location_category_id' => $location->location_category_id ? (string) $location->location_category_id : null,
            'location_category_name' => $location->locationCategory?->name,
            // 'location_category' => $location->locationCategory ? [
            //     'id' => (string) $location->locationCategory->id,
            //     'name' => $location->locationCategory->name,
            // ] : null,
            // 'data_source' => $location->data_source,
            // 'verification_status' => $location->verification_status,
            'details' => $location->plnChargerLocationDetails->map(function ($detail) {
                return [
                    'power' => $detail->power,
                    'is_active_charger' => (bool) $detail->is_active_charger,
                    'count_connector_charger' => $detail->count_connector_charger,
                    'operation_date' => $detail->operation_date,
                    'year' => $detail->year,
                    'charger_category' => $detail->chargerCategory?->name,
                    'merk_charger' => $detail->merkCharger?->name,
                    'charging_type' => $detail->chargingType?->name,
                ];
            })->all(),
        ];
    }
}
