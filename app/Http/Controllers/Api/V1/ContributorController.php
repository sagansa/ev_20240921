<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\ContributorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributorController extends Controller
{
    /**
     * Get the authenticated contributor's profile.
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        
        $contributorProfile = $user->contributorProfile()->first();
        
        if (!$contributorProfile) {
            // Create a default profile if it doesn't exist
            $contributorProfile = ContributorProfile::create([
                'user_id' => $user->id,
                'credibility_score' => 0,
                'total_contributions' => 0,
                'approved_contributions' => 0,
                'rejected_contributions' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contributor profile retrieved successfully',
            'data' => $contributorProfile,
        ]);
    }

    /**
     * Get the contributor leaderboard.
     */
    public function leaderboard(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->limit ?? 10;

        $contributors = ContributorProfile::with('user')
            ->orderBy('credibility_score', 'desc')
            ->orderBy('total_contributions', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Contributor leaderboard retrieved successfully',
            'data' => $contributors,
        ]);
    }

    /**
     * Get a specific contributor's history.
     */
    public function history($id)
    {
        // Only allow users to see their own history or admins to see any history
        $user = Auth::user();
        
        if ($id != $user->id && !($user->hasRole('admin') || $user->hasRole('super_admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to contributor history',
            ], 403);
        }

        $contributorProfile = ContributorProfile::where('user_id', $id)->first();
        
        if (!$contributorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Contributor profile not found',
            ], 404);
        }

        // Get all contributions from this user
        $contributions = [
            'locations_added' => $contributorProfile->getCommunityLocations,
            'reports_submitted' => $contributorProfile->getLocationReports,
            'updates_suggested' => $contributorProfile->getLocationUpdates,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Contributor history retrieved successfully',
            'data' => [
                'profile' => $contributorProfile,
                'contributions' => $contributions,
            ],
        ]);
    }
}