<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of active advertisements.
     */
    public function index(Request $request)
    {
        $request->validate([
            'platform' => 'nullable|in:mobile,web,both',
            'position' => 'nullable|in:banner,interstitial,native',
        ]);

        $query = Advertisement::active();

        if ($request->platform) {
            $query->forPlatform($request->platform);
        }

        if ($request->position) {
            $query->where('position', $request->position);
        }

        $advertisements = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Advertisements retrieved successfully',
            'data' => $advertisements,
        ]);
    }

    /**
     * Store a newly created advertisement (admin only).
     */
    public function store(Request $request)
    {
        // Only admins can create advertisements
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'target_url' => 'nullable|url',
            'platform' => 'required|in:mobile,web,both',
            'position' => 'required|in:banner,interstitial,native',
            'is_active' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $advertisement = Advertisement::create([
            'title' => $request->title,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'target_url' => $request->target_url,
            'platform' => $request->platform,
            'position' => $request->position,
            'is_active' => $request->is_active ?? true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'data' => $advertisement,
        ], 201);
    }

    /**
     * Display the specified advertisement.
     */
    public function show(Advertisement $advertisement)
    {
        return response()->json([
            'success' => true,
            'message' => 'Advertisement retrieved successfully',
            'data' => $advertisement,
        ]);
    }

    /**
     * Update the specified advertisement in storage (admin only).
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        // Only admins can update advertisements
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'target_url' => 'nullable|url',
            'platform' => 'sometimes|in:mobile,web,both',
            'position' => 'sometimes|in:banner,interstitial,native',
            'is_active' => 'boolean',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $advertisement->update($request->only([
            'title',
            'description',
            'image_url',
            'target_url',
            'platform',
            'position',
            'is_active',
            'start_date',
            'end_date'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $advertisement,
        ]);
    }

    /**
     * Remove the specified advertisement from storage (admin only).
     */
    public function destroy(Advertisement $advertisement)
    {
        // Only admins can delete advertisements
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $advertisement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully',
        ]);
    }

    /**
     * Record an advertisement impression.
     */
    public function recordImpression(Request $request, Advertisement $advertisement)
    {
        $advertisement->increment('impression_count');

        return response()->json([
            'success' => true,
            'message' => 'Impression recorded successfully',
            'data' => [
                'advertisement_id' => $advertisement->id,
                'new_impression_count' => $advertisement->impression_count,
            ],
        ]);
    }

    /**
     * Record an advertisement click.
     */
    public function recordClick(Request $request, Advertisement $advertisement)
    {
        $advertisement->increment('click_count');

        return response()->json([
            'success' => true,
            'message' => 'Click recorded successfully',
            'data' => [
                'advertisement_id' => $advertisement->id,
                'new_click_count' => $advertisement->click_count,
            ],
        ]);
    }

    /**
     * Get advertisements for mobile platform.
     */
    public function mobile(Request $request)
    {
        $advertisements = Advertisement::active()
            ->forPlatform('mobile')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Mobile advertisements retrieved successfully',
            'data' => $advertisements,
        ]);
    }

    /**
     * Get advertisements for web platform.
     */
    public function web(Request $request)
    {
        $advertisements = Advertisement::active()
            ->forPlatform('web')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Web advertisements retrieved successfully',
            'data' => $advertisements,
        ]);
    }
}