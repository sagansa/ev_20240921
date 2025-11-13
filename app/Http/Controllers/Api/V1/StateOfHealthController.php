<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
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
            ->with(['vehicle'])
            ->orderBy('date', 'desc');

        // Apply filters if provided
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $stateOfHealths = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'State of health records retrieved successfully',
            'data' => $stateOfHealths,
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

        $stateOfHealth = $user->stateOfHealths()->create([
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'km' => $request->km,
            'percentage' => $request->percentage,
            'remaining_battery' => $request->remaining_battery,
        ]);

        $stateOfHealth->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record created successfully',
            'data' => $stateOfHealth,
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
        
        $stateOfHealth->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record retrieved successfully',
            'data' => $stateOfHealth,
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
        ]);

        $stateOfHealth->update($request->only([
            'date',
            'km',
            'percentage',
            'remaining_battery'
        ]));

        $stateOfHealth->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'State of health record updated successfully',
            'data' => $stateOfHealth,
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
     */
    public function trendAnalysis(Request $request, $vehicleId = null)
    {
        $user = Auth::user();
        
        if ($vehicleId) {
            // Verify user owns the vehicle
            $vehicle = $user->vehicles()->find($vehicleId);
            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to vehicle',
                ], 403);
            }
            
            $stateOfHealthRecords = $user->stateOfHealths()
                ->where('vehicle_id', $vehicleId)
                ->orderBy('date', 'asc')
                ->get();
        } else {
            $stateOfHealthRecords = $user->stateOfHealths()
                ->orderBy('date', 'asc')
                ->get();
        }

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