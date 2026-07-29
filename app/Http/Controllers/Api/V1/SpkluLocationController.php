<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\SpkluLocationResource;
use App\Models\SpkluLocation;
use Illuminate\Http\Request;

class SpkluLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = SpkluLocation::with(['chargerBoxes', 'provider']);

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
            $query->where('type_charge', $request->type_charge);
        }

        if ($request->filled('watt')) {
            $query->where('watt', $request->watt);
        }

        $lat = $request->filled('lat') ? (float) $request->lat : null;
        $lng = $request->filled('lng') ? (float) $request->lng : null;
        $radius = $request->filled('radius') ? (float) $request->radius : null;

        if ($lat !== null && $lng !== null && $radius !== null) {
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

            $query->select('spklu_locations.*')
                ->selectRaw($haversine . ' AS distance')
                ->whereRaw($haversine . ' <= ' . $radius)
                ->orderBy('distance');
        }

        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        $locations = $query->paginate($perPage, ['*'], 'page', $page);

        return SpkluLocationResource::collection($locations)
            ->additional(['status' => 'success']);
    }

    public function show($id)
    {
        // Mobile mengirim id = external_id (lihat SpkluLocationResource::id),
        // jadi lookup harus berdasarkan external_id, bukan PK.
        $location = SpkluLocation::with(['chargerBoxes', 'provider'])
            ->where('external_id', $id)
            ->firstOrFail();

        return SpkluLocationResource::make($location)
            ->additional(['status' => 'success']);
    }

    public function metaFilters()
    {
        $provinces = SpkluLocation::select('provinsi')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi');

        $chargeTypes = SpkluLocation::select('type_charge')
            ->distinct()
            ->orderBy('type_charge')
            ->pluck('type_charge')
            ->filter();

        return response()->json([
            'status' => 'success',
            'data' => [
                'provinces' => $provinces,
                'charge_types' => $chargeTypes->values(),
            ],
        ]);
    }
}
