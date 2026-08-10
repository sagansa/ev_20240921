<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SpkluLocationResource;
use App\Models\ChargingStation;
use App\Models\SavedStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bookmark lokasi SPKLU oleh user (Fase 3 — Peta User).
 *
 * - POST /stations/{station}/save → toggle save/unsaved (idempotent).
 * - GET /me/saved-stations → list station tersimpan user (utk peta "Peta Saya").
 *
 * Tidak gated source — semua station (PLN/ESDM) bisa di-bookmark. Data station
 * di-serve via SpkluLocationResource (JOIN charging_stations) agar info pin
 * (nama/lat/lng/provider) selalu segar.
 */
class SavedStationController extends Controller
{
    /** Toggle bookmark: save bila belum, unsave bila sudah. Return status baru. */
    public function toggle(Request $request, ChargingStation $station): JsonResponse
    {
        $existing = SavedStation::query()
            ->where('user_id', Auth::id())
            ->where('charging_station_id', $station->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lokasi dihapus dari tersimpan',
                'data' => ['is_saved' => false],
            ]);
        }

        SavedStation::create([
            'user_id' => Auth::id(),
            'charging_station_id' => $station->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi disimpan',
            'data' => ['is_saved' => true],
        ], 201);
    }

    /** Cek apakah station sudah di-bookmark user (utk state tombol). */
    public function check(Request $request, ChargingStation $station): JsonResponse
    {
        $isSaved = SavedStation::query()
            ->where('user_id', Auth::id())
            ->where('charging_station_id', $station->id)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => ['is_saved' => $isSaved],
        ]);
    }

    /**
     * List station tersimpan user — di-serve sebagai SpkluLocationResource
     * (sama shape dgn GET /spklu) agar mobile bisa render pin langsung.
     */
    public function index(Request $request): JsonResponse
    {
        $stations = ChargingStation::with(['chargerBoxes.connectors', 'provider'])
            ->whereHas('savedStations', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Saved stations retrieved successfully',
            'data' => SpkluLocationResource::collection($stations),
        ]);
    }
}
