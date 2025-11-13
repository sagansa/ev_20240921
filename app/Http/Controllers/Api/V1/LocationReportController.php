<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\ChargerLocation;
use App\Models\LocationReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationReportController extends Controller
{
    /**
     * Report an issue with a location.
     */
    public function reportLocation(Request $request, ChargerLocation $chargerLocation)
    {
        $request->validate([
            'report_type' => 'required|in:closure,info_update,charger_count,status_change,duplicate',
            'description' => 'required|string|max:1000',
            'evidence_photos' => 'nullable|array',
            'evidence_photos.*' => 'image|max:2048', // Max 2MB per image
        ]);

        $report = $chargerLocation->locationReports()->create([
            'reporter_id' => Auth::id(),
            'report_type' => $request->report_type,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Store evidence photos if provided
        if ($request->hasFile('evidence_photos')) {
            $photoPaths = [];
            foreach ($request->file('evidence_photos') as $photo) {
                $path = $photo->store('location-reports', 'public');
                $photoPaths[] = $path;
            }
            
            // Assuming we have a way to store multiple photo paths
            // This might require a separate LocationReportPhoto model
            // For now, just store the first photo as a string
            $report->update([
                'evidence_photos' => json_encode($photoPaths)
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location report submitted successfully',
            'data' => $report,
        ], 201);
    }

    /**
     * Get reports for a specific location (admin only).
     */
    public function getLocationReports(Request $request, ChargerLocation $chargerLocation)
    {
        // Only admins can see location reports
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $reports = $chargerLocation->locationReports()
            ->with(['reporter'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Location reports retrieved successfully',
            'data' => $reports,
        ]);
    }

    /**
     * Get all pending reports (admin only).
     */
    public function getPendingReports(Request $request)
    {
        // Only admins can see pending reports
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $reports = LocationReport::where('status', 'pending')
            ->with(['chargerLocation', 'reporter'])
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Pending reports retrieved successfully',
            'data' => $reports,
        ]);
    }

    /**
     * Process a report (admin only).
     */
    public function processReport(Request $request, LocationReport $report)
    {
        // Only admins can process reports
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,resolved',
            'admin_notes' => 'nullable|string',
        ]);

        $report->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        // If the report is for a PLN location and approved/rejected, special handling might be needed
        if ($report->chargerLocation->data_source === 'pln' && in_array($request->status, ['approved', 'resolved'])) {
            // Special processing for PLN location updates
            // This could trigger notifications or special workflows
        }

        return response()->json([
            'success' => true,
            'message' => 'Location report processed successfully',
            'data' => $report,
        ]);
    }
}