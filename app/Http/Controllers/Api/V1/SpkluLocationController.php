<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SpkluLocationResource;
use App\Models\ChargingStation;
use Illuminate\Http\Request;

/**
 * Serving SPKLU dari lapisan KANONIK (charging_stations + charging_station_chargers).
 *
 * Contract mobile dipertahankan: GET /api/v1/spklu → {status, data:[…], links,
 * meta}; id publik = charging_stations.id. Source bisa berganti (ESDM → lainnya)
 * tanpa mengubah kontrak ini — cukup rehydrate tabel kanonik.
 */
class SpkluLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = ChargingStation::with(['chargerBoxes.connectors', 'provider'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lokasi', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        if ($request->filled('provinsi')) {
            $query->where('provinsi', trim($request->provinsi));
        }

        if ($request->filled('type_charge')) {
            $types = $this->expandTypeChargeFilter($request->type_charge);
            $query->whereIn('type_charge', $types);
        }

        if ($request->filled('watt')) {
            $query->where('watt', $request->watt);
        }

        $lat = $request->filled('lat') ? (float) $request->lat : null;
        $lng = $request->filled('lng') ? (float) $request->lng : null;
        $radius = $request->filled('radius') ? (float) $request->radius : null;

        if ($lat !== null && $lng !== null && $radius !== null) {
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

            $query->select('charging_stations.*')
                ->selectRaw($haversine.' AS distance')
                ->whereRaw($haversine.' <= '.$radius)
                ->orderBy('distance');
        }

        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        $locations = $query->paginate($perPage, ['*'], 'page', $page);

        return SpkluLocationResource::collection($locations)
            ->additional(['status' => 'success']);
    }

    /**
     * Map filter speed id (dipakai mobile: "medium"/"fast"/"ultra_fast") ke
     * label type_charge verbatim ESDM yang tersimpan di canonical.
     */
    private function expandTypeChargeFilter(string $typeCharge): array
    {
        $map = [
            'medium' => ['Medium Charging', 'Slow Charging'],
            'standard' => ['Medium Charging', 'Slow Charging'],
            'fast' => ['Fast Charging'],
            'ultrafast' => ['Ultra Fast Charging'],
            'ultra_fast' => ['Ultra Fast Charging'],
        ];

        return $map[strtolower(trim($typeCharge))] ?? [$typeCharge];
    }

    public function show($id)
    {
        $location = ChargingStation::with(['chargerBoxes.connectors', 'provider'])->findOrFail($id);

        return SpkluLocationResource::make($location)
            ->additional(['status' => 'success']);
    }

    public function metaFilters()
    {
        $provinces = ChargingStation::select('provinsi')
            ->whereNotNull('provinsi')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi');

        $chargeTypes = ChargingStation::select('type_charge')
            ->whereNotNull('type_charge')
            ->distinct()
            ->orderBy('type_charge')
            ->pluck('type_charge');

        return response()->json([
            'status' => 'success',
            'data' => [
                'provinces' => $provinces,
                'charge_types' => $chargeTypes->values(),
            ],
        ]);
    }
}
