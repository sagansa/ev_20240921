<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChargingSessionController extends Controller
{
    /**
     * Display a listing of the user's charging sessions.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->charges()
            ->with([
                'vehicle',
                'chargerLocation',
                'charger'
            ]);

        // Apply filters if provided
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        
        if ($request->has('charger_location_id')) {
            $query->where('charger_location_id', $request->charger_location_id);
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $chargingSessions = $query->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Charging sessions retrieved successfully',
            'data' => $chargingSessions,
        ]);
    }

    /**
     * Store a newly created charging session.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'charger_location_id' => 'nullable|exists:charger_locations,id',
            'charger_id' => 'nullable|exists:chargers,id',
            'date' => 'required|date',
            'km_before' => 'required|integer|min:0',
            'km_now' => 'required|integer|min:0',
            'start_charging_now' => 'required|integer|min:0',
            'finish_charging_now' => 'nullable|integer|min:0',
            'finish_charging_before' => 'required|integer|min:0',
            'parking' => 'nullable|integer|min:0',
            'kWh' => 'nullable|numeric|min:0',
            'street_lighting_tax' => 'nullable|integer|min:0',
            'value_added_tax' => 'nullable|integer|min:0',
            'admin_cost' => 'nullable|integer|min:0',
            'total_cost' => 'nullable|integer|min:0',
            'is_finish_charging' => 'boolean',
            'is_kwh_measured' => 'boolean',
        ]);

        $user = Auth::user();
        
        // Verify user owns the vehicle
        $vehicle = $user->vehicles()->find($request->vehicle_id);
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $chargingSession = $user->charges()->create([
            'vehicle_id' => $request->vehicle_id,
            'charger_location_id' => $request->charger_location_id,
            'charger_id' => $request->charger_id,
            'date' => $request->date,
            'km_before' => $request->km_before,
            'km_now' => $request->km_now,
            'start_charging_now' => $request->start_charging_now,
            'finish_charging_now' => $request->finish_charging_now,
            'finish_charging_before' => $request->finish_charging_before,
            'parking' => $request->parking ?? 0,
            'kWh' => $request->kWh,
            'street_lighting_tax' => $request->street_lighting_tax ?? 0,
            'value_added_tax' => $request->value_added_tax ?? 0,
            'admin_cost' => $request->admin_cost ?? 0,
            'total_cost' => $request->total_cost ?? 0,
            'is_finish_charging' => $request->is_finish_charging ?? false,
            'is_kwh_measured' => $request->is_kwh_measured ?? false,
            'user_id' => $user->id,
        ]);

        $chargingSession->load([
            'vehicle',
            'chargerLocation',
            'charger'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging session created successfully',
            'data' => $chargingSession,
        ], 201);
    }

    /**
     * Display the specified charging session.
     */
    public function show(Charge $chargingSession)
    {
        $user = Auth::user();
        
        if ((string) $chargingSession->user_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to charging session',
            ], 403);
        }
        
        $chargingSession->load([
            'vehicle',
            'chargerLocation',
            'charger'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging session retrieved successfully',
            'data' => $chargingSession,
        ]);
    }

    /**
     * Update the specified charging session in storage.
     */
    public function update(Request $request, Charge $chargingSession)
    {
        $user = Auth::user();
        
        if ((string) $chargingSession->user_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to charging session',
            ], 403);
        }

        $request->validate([
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'charger_location_id' => 'sometimes|exists:charger_locations,id',
            'charger_id' => 'sometimes|exists:chargers,id',
            'date' => 'sometimes|date',
            'km_before' => 'sometimes|integer|min:0',
            'km_now' => 'sometimes|integer|min:0',
            'start_charging_now' => 'sometimes|integer|min:0',
            'finish_charging_now' => 'nullable|integer|min:0',
            'finish_charging_before' => 'sometimes|integer|min:0',
            'parking' => 'sometimes|integer|min:0',
            'kWh' => 'sometimes|numeric|min:0',
            'street_lighting_tax' => 'sometimes|integer|min:0',
            'value_added_tax' => 'sometimes|integer|min:0',
            'admin_cost' => 'sometimes|integer|min:0',
            'total_cost' => 'sometimes|integer|min:0',
            'is_finish_charging' => 'boolean',
            'is_kwh_measured' => 'boolean',
        ]);

        $chargingSession->update($request->only([
            'vehicle_id',
            'charger_location_id',
            'charger_id',
            'date',
            'km_before',
            'km_now',
            'start_charging_now',
            'finish_charging_now',
            'finish_charging_before',
            'parking',
            'kWh',
            'street_lighting_tax',
            'value_added_tax',
            'admin_cost',
            'total_cost',
            'is_finish_charging',
            'is_kwh_measured'
        ]));

        $chargingSession->load([
            'vehicle',
            'chargerLocation',
            'charger'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Charging session updated successfully',
            'data' => $chargingSession,
        ]);
    }

    /**
     * Remove the specified charging session from storage.
     */
    public function destroy(Charge $chargingSession)
    {
        $user = Auth::user();
        
        if ((string) $chargingSession->user_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to charging session',
            ], 403);
        }

        $chargingSession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Charging session deleted successfully',
        ]);
    }
}
