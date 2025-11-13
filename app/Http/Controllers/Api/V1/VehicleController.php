<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * Display a listing of the user's vehicles.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $vehicles = $user->vehicles()
            ->with([
                'brandVehicle',
                'modelVehicle', 
                'typeVehicle'
            ])
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Vehicles retrieved successfully',
            'data' => $vehicles,
        ]);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brand_vehicles,id',
            'model_id' => 'required|exists:model_vehicles,id',
            'type_id' => 'required|exists:type_vehicles,id',
            'name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20',
            'year' => 'nullable|integer|min:1900|max:2100',
            'battery_capacity' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();
        
        $vehicle = $user->vehicles()->create([
            'user_id' => $user->id,
            'brand_id' => $request->brand_id,
            'model_id' => $request->model_id,
            'type_id' => $request->type_id,
            'name' => $request->name,
            'license_plate' => $request->license_plate,
            'year' => $request->year,
            'battery_capacity' => $request->battery_capacity,
        ]);

        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => $vehicle,
        ], 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle)
    {
        $user = Auth::user();
        
        // Ensure user can only access their own vehicles
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }
        
        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle retrieved successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $user = Auth::user();
        
        // Ensure user can only update their own vehicles
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }
        
        $request->validate([
            'brand_id' => 'nullable|exists:brand_vehicles,id',
            'model_id' => 'nullable|exists:model_vehicles,id',
            'type_id' => 'nullable|exists:type_vehicles,id',
            'name' => 'nullable|string|max:255',
            'license_plate' => 'nullable|string|max:20',
            'year' => 'nullable|integer|min:1900|max:2100',
            'battery_capacity' => 'nullable|numeric|min:0',
        ]);
        
        $vehicle->update($request->only([
            'brand_id',
            'model_id',
            'type_id',
            'name',
            'license_plate',
            'year',
            'battery_capacity'
        ]));

        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * Remove the specified vehicle from storage (soft delete).
     */
    public function destroy(Vehicle $vehicle)
    {
        $user = Auth::user();
        
        // Ensure user can only delete their own vehicles
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }
}