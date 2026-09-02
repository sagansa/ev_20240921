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

    /** GET /vehicle-market/trend?year=&brand=&model= — unit bulanan per powertrain; year=all → pola musiman. */
    public function trend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'string', 'max:10'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->trend(
                $this->normalizeYear($validated['year'] ?? null),
                $validated['brand'] ?? null,
                $validated['model'] ?? null,
            ),
        ]);
    }

    /** GET /vehicle-market/composition?year=&powertrain= — komposisi per kategori kendaraan. */
    public function composition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'string', 'max:10'],
            'powertrain' => ['nullable', 'string', 'in:BEV,PHEV,HEV,ICE,ALL,EV'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->categoryComposition(
                $this->normalizeYear($validated['year'] ?? null),
                $validated['powertrain'] ?? 'ALL',
            ),
        ]);
    }

    /** GET /vehicle-market/top?year=&powertrain=&brand=&limit= — top brand & model. */
    public function top(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'string', 'max:10'],
            'powertrain' => ['nullable', 'string', 'in:BEV,PHEV,HEV,ICE,ALL,EV'],
            'brand' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->top(
                $this->normalizeYear($validated['year'] ?? null),
                $validated['powertrain'] ?? null,
                $validated['brand'] ?? null,
                $validated['limit'] ?? 10,
            ),
        ]);
    }

    /** GET /vehicle-market/catalog?year= — peta brand → model utk filter. */
    public function catalog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'string', 'max:10'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->catalog($this->normalizeYear($validated['year'] ?? null)),
        ]);
    }

    /**
     * Normalisasi year: 'all' → sentinel 'all'; numerik 2000-2100 → int;
     * lainnya → null (fallback tahun terbaru).
     */
    private function normalizeYear(mixed $raw): int|string|null
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $str = trim((string) $raw);
        if (strcasecmp($str, 'all') === 0) {
            return 'all';
        }
        if (is_numeric($str)) {
            $int = (int) $str;
            if ($int >= 2000 && $int <= 2100) {
                return $int;
            }
        }
        return null;
    }

    /** GET /vehicle-market/model-history?brand=&model= — histori tahunan model spesifik. */
    public function modelHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->market->modelHistory($validated['brand'], $validated['model']),
        ]);
    }
}
