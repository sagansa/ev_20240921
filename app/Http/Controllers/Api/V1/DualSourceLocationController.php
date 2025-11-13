<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\ChargerLocation;
use App\Models\PlnChargerLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DualSourceLocationController extends Controller
{
    /**
     * Import PLN locations (admin only).
     */
    public function importPlnLocations(Request $request)
    {
        // Only super admins can import PLN locations
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'locations' => 'required|array',
            'locations.*.name' => 'required|string|max:255',
            'locations.*.latitude' => 'required|numeric',
            'locations.*.longitude' => 'required|numeric',
            'locations.*.address' => 'required|string|max:500',
            'locations.*.provider_id' => 'required|exists:providers,id',
        ]);

        $createdLocations = [];
        $failedImports = [];

        foreach ($request->locations as $locationData) {
            try {
                $plnLocation = PlnChargerLocation::create([
                    'name' => $locationData['name'],
                    'latitude' => $locationData['latitude'],
                    'longitude' => $locationData['longitude'],
                    'address' => $locationData['address'],
                    'provider_id' => $locationData['provider_id'],
                    'location_on' => 2, // PLN locations
                    'status' => 1, // Active
                ]);

                $createdLocations[] = $plnLocation;
            } catch (\Exception $e) {
                $failedImports[] = [
                    'data' => $locationData,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'PLN locations import completed',
            'data' => [
                'created_count' => count($createdLocations),
                'failed_count' => count($failedImports),
                'created_locations' => $createdLocations,
                'failed_imports' => $failedImports,
            ]
        ]);
    }

    /**
     * Update PLN location (admin only).
     */
    public function updatePlnLocation(Request $request, PlnChargerLocation $plnLocation)
    {
        // Only super admins can update PLN locations
        if (!Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'address' => 'sometimes|string|max:500',
            'provider_id' => 'sometimes|exists:providers,id',
            'status' => 'sometimes|integer',
        ]);

        $plnLocation->update($request->only([
            'name',
            'latitude',
            'longitude',
            'address',
            'provider_id',
            'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'PLN location updated successfully',
            'data' => $plnLocation,
        ]);
    }

    /**
     * Submit a new community location.
     */
    public function submitCommunityLocation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string|max:500',
            'provider_id' => 'required|exists:providers,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048', // Max 2MB
        ]);

        // Create a new ChargerLocation with pending verification status
        $chargerLocation = new ChargerLocation();
        $chargerLocation->name = $request->name;
        $chargerLocation->latitude = $request->latitude;
        $chargerLocation->longitude = $request->longitude;
        $chargerLocation->address = $request->address;
        $chargerLocation->provider_id = $request->provider_id;
        $chargerLocation->description = $request->description;
        $chargerLocation->location_on = 1; // Community location
        $chargerLocation->status = 1; // Pending verification
        $chargerLocation->user_id = Auth::id();
        $chargerLocation->data_source = 'community';
        $chargerLocation->verification_status = 'pending_verification';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('charger-locations', 'public');
            $chargerLocation->image = $path;
        }

        $chargerLocation->save();

        return response()->json([
            'success' => true,
            'message' => 'Community location submitted successfully. Awaiting verification.',
            'data' => $chargerLocation,
        ], 201);
    }

    /**
     * Get all pending community locations for verification (admin only).
     */
    public function getPendingCommunityLocations(Request $request)
    {
        // Only admins can see pending locations
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $pendingLocations = ChargerLocation::where('data_source', 'community')
            ->where('verification_status', 'pending_verification')
            ->with(['provider', 'user'])
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Pending community locations retrieved successfully',
            'data' => $pendingLocations,
        ]);
    }

    /**
     * Verify a community location (admin only).
     */
    public function verifyCommunityLocation(Request $request, ChargerLocation $chargerLocation)
    {
        // Only admins can verify locations
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $chargerLocation->update([
            'status' => 2, // Active
            'verification_status' => 'community_verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Add verification notes to the audit log
        // This would require a LocationAuditLog model that doesn't exist yet

        return response()->json([
            'success' => true,
            'message' => 'Community location verified successfully',
            'data' => $chargerLocation,
        ]);
    }

    /**
     * Reject a community location (admin only).
     */
    public function rejectCommunityLocation(Request $request, ChargerLocation $chargerLocation)
    {
        // Only admins can reject locations
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $chargerLocation->update([
            'status' => 4, // Rejected
            'verification_status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Add rejection notes to the audit log
        // This would require a LocationAuditLog model that doesn't exist yet

        return response()->json([
            'success' => true,
            'message' => 'Community location rejected successfully',
            'data' => $chargerLocation,
            'reason' => $request->reason,
        ]);
    }

    /**
     * Detect potential duplicates.
     */
    public function detectDuplicates(Request $request)
    {
        // Only admins can view potential duplicates
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // This would implement geospatial proximity checks
        // For now, return placeholder implementation
        
        return response()->json([
            'success' => false,
            'message' => 'Duplicate detection algorithm not yet implemented',
        ], 501);
    }

    /**
     * Consolidate duplicate locations (admin only).
     */
    public function consolidateLocations(Request $request)
    {
        // Only admins can consolidate locations
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'primary_location_id' => 'required',
            'duplicate_location_id' => 'required',
            'resolution_action' => 'required|in:merge,keep_separate,delete_duplicate',
        ]);

        // This would require a LocationDuplication model and complex logic
        // For now, return placeholder implementation
        
        return response()->json([
            'success' => false,
            'message' => 'Location consolidation algorithm not yet implemented',
        ], 501);
    }
}
