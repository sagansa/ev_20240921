<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\StateOfHealthResource;
use App\Models\Battery;
use App\Models\StateOfHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StateOfHealthController extends Controller
{
    /**
     * Display a listing of the user's state of health records.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->stateOfHealths()
            ->with(['vehicle', 'battery'])
            ->orderBy('date', 'desc');

        // Apply filters if provided
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->has('battery_id')) {
            $query->where('battery_id', $request->battery_id);
        }

        $stateOfHealths = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'State of health records retrieved successfully',
            'data' => StateOfHealthResource::collection($stateOfHealths->getCollection()),
            'meta' => [
                'current_page' => $stateOfHealths->currentPage(),
                'last_page' => $stateOfHealths->lastPage(),
                'per_page' => $stateOfHealths->perPage(),
                'total' => $stateOfHealths->total(),
                'has_more' => $stateOfHealths->hasMorePages(),
            ],
        ]);
    }

    /**
     * Store a newly created state of health record.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'date' => 'required|date',
            'km' => 'required|integer|min:0',
            'percentage' => 'required|numeric|min:0|max:100',
            'remaining_battery' => 'nullable|numeric|min:0',
            'battery_id' => 'nullable|uuid',
        ]);

        $user = Auth::user();

        // Verify user owns the vehicle
        $vehicle = $user->vehicles()->find($request->vehicle_id);
        if (! $vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $batteryId = $request->battery_id;
        if ($batteryId) {
            $battery = Battery::where('user_id', $user->id)->find($batteryId);
            if (! $battery || (string) $battery->vehicle_id !== (string) $vehicle->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to battery',
                ], 403);
            }
        } else {
            // Auto-resolve: baterai aktif kendaraan pada km tsb.
            $batteryId = Battery::resolveForKm($vehicle->id, (float) $request->km)?->id;
        }

        $stateOfHealth = $user->stateOfHealths()->create([
            'vehicle_id' => $request->vehicle_id,
            'battery_id' => $batteryId,
            'date' => $request->date,
            'km' => $request->km,
            'percentage' => $request->percentage,
            'remaining_battery' => $request->remaining_battery,
        ]);

        $stateOfHealth->load(['vehicle', 'battery']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record created successfully',
            'data' => new StateOfHealthResource($stateOfHealth),
        ], 201);
    }

    /**
     * Display the specified state of health record.
     */
    public function show(StateOfHealth $stateOfHealth)
    {
        $user = Auth::user();

        // Ensure user can only access their own state of health records
        if ($stateOfHealth->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to state of health record',
            ], 403);
        }

        $stateOfHealth->load(['vehicle', 'battery']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record retrieved successfully',
            'data' => new StateOfHealthResource($stateOfHealth),
        ]);
    }

    /**
     * Update the specified state of health record in storage.
     */
    public function update(Request $request, StateOfHealth $stateOfHealth)
    {
        $user = Auth::user();

        // Ensure user can only update their own state of health records
        if ($stateOfHealth->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to state of health record',
            ], 403);
        }

        $request->validate([
            'date' => 'sometimes|date',
            'km' => 'sometimes|integer|min:0',
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'remaining_battery' => 'nullable|numeric|min:0',
            'battery_id' => 'nullable|uuid',
        ]);

        $data = $request->only([
            'date',
            'km',
            'percentage',
            'remaining_battery',
            'battery_id',
        ]);

        // Auto-resolve battery saat update km (tanpa battery_id eksplisit).
        if (array_key_exists('battery_id', $data) && empty($data['battery_id'])) {
            $km = $data['km'] ?? $stateOfHealth->km;
            $data['battery_id'] = Battery::resolveForKm($stateOfHealth->vehicle_id, (float) $km)?->id;
        } elseif (! empty($data['battery_id'])) {
            $battery = Battery::where('user_id', $user->id)->find($data['battery_id']);
            if (! $battery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to battery',
                ], 403);
            }
        }

        $stateOfHealth->update($data);

        $stateOfHealth->load(['vehicle', 'battery']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record updated successfully',
            'data' => new StateOfHealthResource($stateOfHealth),
        ]);
    }

    /**
     * Remove the specified state of health record from storage.
     */
    public function destroy(StateOfHealth $stateOfHealth)
    {
        $user = Auth::user();

        // Ensure user can only delete their own state of health records
        if ($stateOfHealth->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to state of health record',
            ], 403);
        }

        $stateOfHealth->delete();

        return response()->json([
            'success' => true,
            'message' => 'State of health record deleted successfully',
        ]);
    }

    /**
     * Generate state of health trend analysis for a specific vehicle.
     * Bisa di-segment per baterai via query param `battery_id` (opsional).
     */
    public function trendAnalysis(Request $request, $vehicleId = null)
    {
        $user = Auth::user();

        $query = $user->stateOfHealths()->with(['vehicle', 'battery']);

        if ($vehicleId) {
            // Verify user owns the vehicle
            $vehicle = $user->vehicles()->find($vehicleId);
            if (! $vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to vehicle',
                ], 403);
            }

            $query->where('vehicle_id', $vehicleId);
        }

        if ($request->filled('battery_id')) {
            $batteryId = $request->battery_id;
            $battery = Battery::where('user_id', $user->id)->find($batteryId);
            if (! $battery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to battery',
                ], 403);
            }
            $query->where('battery_id', $batteryId);
        }

        $stateOfHealthRecords = $query->orderBy('date', 'asc')->orderBy('created_at', 'asc')->get();

        // Calculate trends and other analytics
        $analysis = [];
        $previousRecord = null;

        foreach ($stateOfHealthRecords as $record) {
            $analysis[] = [
                'date' => $record->date,
                'percentage' => $record->percentage,
                'km' => $record->km,
                'degradation_rate' => null,
            ];

            $previousRecord = $record;
        }

        // Calculate degradation rate if more than one record exists
        if (count($stateOfHealthRecords) > 1) {
            $firstRecord = $stateOfHealthRecords->first();
            $lastRecord = $stateOfHealthRecords->last();

            $daysDiff = $firstRecord->date->diffInDays($lastRecord->date);
            $percentageDiff = $firstRecord->percentage - $lastRecord->percentage;

            $degradationRate = $daysDiff > 0 ? ($percentageDiff / $daysDiff) * 365 : 0; // Annual degradation rate

            // Update the analysis with degradation rates
            for ($i = 1; $i < count($analysis); $i++) {
                if ($i === count($analysis) - 1) { // Last record
                    $analysis[$i]['annual_degradation_rate'] = $degradationRate;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'State of health trend analysis retrieved successfully',
            'data' => [
                'records' => $analysis,
                'summary' => [
                    'total_records' => count($stateOfHealthRecords),
                    'first_date' => count($stateOfHealthRecords) > 0 ? $stateOfHealthRecords->first()->date : null,
                    'last_date' => count($stateOfHealthRecords) > 0 ? $stateOfHealthRecords->last()->date : null,
                    'initial_percentage' => count($stateOfHealthRecords) > 0 ? $stateOfHealthRecords->first()->percentage : null,
                    'latest_percentage' => count($stateOfHealthRecords) > 0 ? $stateOfHealthRecords->last()->percentage : null,
                    'total_degradation' => count($stateOfHealthRecords) > 1
                        ? $stateOfHealthRecords->first()->percentage - $stateOfHealthRecords->last()->percentage
                        : 0,
                ],
            ],
        ]);
    }
}
