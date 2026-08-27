<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\VehicleMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint publik data pasar kendaraan (sumber: import GAIKINDO).
 * Di-cache 24 jam; otomatis segar setelah import baru.
 */
class VehicleMarketController extends Controller
{
    public function __construct(protected VehicleMarketService $market)
    {
    }

    /** GET /vehicle-market/summary — penetrasi BEV per tahun + ringkasan terbaru. */
    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->market->summary(),
        ]);
    }

    /** GET /vehicle-market/trend?year= — unit bulanan per powertrain. */
    public function trend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = $validated['year'] ?? (int) now()->year;

        return response()->json([
            'success' => true,
            'data' => $this->market->trend($year),
        ]);
    }

    /** GET /vehicle-market/top?year=&powertrain=BEV&limit=10 — top brand & model. */
    public function top(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'powertrain' => ['nullable', 'string', 'in:BEV,PHEV,HEV,ICE,ALL,EV'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->top(
                $validated['year'] ?? null,
                $validated['powertrain'] ?? null,
                $validated['limit'] ?? 10,
            ),
        ]);
    }
}
