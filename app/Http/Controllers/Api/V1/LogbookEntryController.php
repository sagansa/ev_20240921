<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\LogbookEntryResource;
use App\Models\ChargingStation;
use App\Models\LogbookEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogbookEntryController extends Controller
{
    /**
     * Display a listing of the authenticated user's logbook entries.
     */
    public function index(Request $request)
    {
        $query = LogbookEntry::where('user_id', Auth::id());

        if ($request->has('charging_station_id')) {
            $query->where('charging_station_id', $request->charging_station_id);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('session_at', [$request->date_from, $request->date_to]);
        }

        // get() (bukan paginate()) supaya Resource::collection() menghasilkan
        // array murni `data: [...]`, cocok dgn kontrak LogbookListResponse.data
        // (List<LogbookEntryDto>) di shared module — paginator justru membungkus
        // data jadi `{data, links, meta}` yang gagal deserialize.
        $entries = $query->orderBy('session_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Logbook entries retrieved successfully',
            'data' => LogbookEntryResource::collection($entries),
        ]);
    }

    /**
     * Store a newly created logbook entry.
     */
    public function store(Request $request)
    {
        $request->validate([
            'charging_station_id' => 'nullable|integer',
            'station_name' => 'required_without:charging_station_id|nullable|string|max:255',
            'session_at' => 'required|date',
            'odometer_km' => 'nullable|numeric|min:0',
            'distance_driven_km' => 'nullable|numeric|min:0',
            'energy_kwh' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'parking_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $entry = LogbookEntry::create([
            'user_id' => Auth::id(),
            'charging_station_id' => $request->charging_station_id,
            ...$this->stationSnapshot($request->charging_station_id, $request->station_name),
            'session_at' => $request->session_at,
            'odometer_km' => $request->odometer_km,
            'distance_driven_km' => $request->distance_driven_km,
            'energy_kwh' => $request->energy_kwh,
            'total_cost' => $request->total_cost,
            'parking_cost' => $request->parking_cost ?? 0,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logbook entry created successfully',
            'data' => new LogbookEntryResource($entry),
        ], 201);
    }

    /**
     * Display the specified logbook entry.
     */
    public function show(LogbookEntry $logbookEntry)
    {
        if ((string) $logbookEntry->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to logbook entry',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logbook entry retrieved successfully',
            'data' => new LogbookEntryResource($logbookEntry),
        ]);
    }

    /**
     * Update the specified logbook entry in storage.
     */
    public function update(Request $request, LogbookEntry $logbookEntry)
    {
        if ((string) $logbookEntry->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to logbook entry',
            ], 403);
        }

        $request->validate([
            'charging_station_id' => 'nullable|integer',
            'station_name' => 'nullable|string|max:255',
            'session_at' => 'sometimes|date',
            'odometer_km' => 'nullable|numeric|min:0',
            'distance_driven_km' => 'nullable|numeric|min:0',
            'energy_kwh' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'parking_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data = $request->only([
            'charging_station_id',
            'session_at',
            'odometer_km',
            'distance_driven_km',
            'energy_kwh',
            'total_cost',
            'parking_cost',
            'notes',
        ]);

        // Re-snapshot station bila charging_station_id ikut dikirim (atau dihapus).
        if ($request->has('charging_station_id')) {
            $data = array_merge(
                $data,
                $this->stationSnapshot($request->charging_station_id, $request->station_name)
            );
        } elseif ($request->has('station_name')) {
            $data['station_name'] = $request->station_name;
        }

        $logbookEntry->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Logbook entry updated successfully',
            'data' => new LogbookEntryResource($logbookEntry),
        ]);
    }

    /**
     * Remove the specified logbook entry from storage.
     */
    public function destroy(LogbookEntry $logbookEntry)
    {
        if ((string) $logbookEntry->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to logbook entry',
            ], 403);
        }

        $logbookEntry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logbook entry deleted successfully',
        ]);
    }

    /**
     * Snapshot denormalized dari charging_stations saat entri dibuat/diupdate.
     * Bila charging_station_id diberikan dan station ditemukan, snapshot
     * mengambil alih station_name manual.
     */
    private function stationSnapshot(?int $stationId, ?string $manualName): array
    {
        if ($stationId) {
            $station = ChargingStation::find($stationId);
            if ($station) {
                return [
                    'station_name' => $station->nama_lokasi,
                    'station_address' => $station->alamat,
                    'station_latitude' => $station->latitude,
                    'station_longitude' => $station->longitude,
                    'station_provider' => $station->provider_name,
                    'station_type_charge' => $station->type_charge,
                ];
            }
        }

        return [
            'station_name' => $manualName,
            'station_address' => null,
            'station_latitude' => null,
            'station_longitude' => null,
            'station_provider' => null,
            'station_type_charge' => null,
        ];
    }
}
