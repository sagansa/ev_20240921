<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\DiscountHomeCharging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeChargingDiscountController extends Controller
{
    /**
     * Display a listing of available home charging discounts.
     */
    public function index(Request $request)
    {
        $query = DiscountHomeCharging::query();

        // Apply filters if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $discounts = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Home charging discounts retrieved successfully',
            'data' => $discounts,
        ]);
    }

    /**
     * Store a newly created home charging discount.
     */
    public function store(Request $request)
    {
        // Only admins can create discounts
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $discount = DiscountHomeCharging::create([
            'name' => $request->name,
            'description' => $request->description,
            'percentage' => $request->percentage,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'conditions' => $request->conditions,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Home charging discount created successfully',
            'data' => $discount,
        ], 201);
    }

    /**
     * Display the specified home charging discount.
     */
    public function show(DiscountHomeCharging $discount)
    {
        return response()->json([
            'success' => true,
            'message' => 'Home charging discount retrieved successfully',
            'data' => $discount,
        ]);
    }

    /**
     * Update the specified home charging discount in storage.
     */
    public function update(Request $request, DiscountHomeCharging $discount)
    {
        // Only admins can update discounts
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $discount->update($request->only([
            'name',
            'description',
            'percentage',
            'start_date',
            'end_date',
            'conditions',
            'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Home charging discount updated successfully',
            'data' => $discount,
        ]);
    }

    /**
     * Remove the specified home charging discount from storage.
     */
    public function destroy(DiscountHomeCharging $discount)
    {
        // Only admins can delete discounts
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Home charging discount deleted successfully',
        ]);
    }

    /**
     * Apply a discount to a charging session calculation.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'discount_id' => 'required|exists:discount_home_chargings,id',
            'original_amount' => 'required|numeric|min:0',
        ]);

        $discount = DiscountHomeCharging::find($request->discount_id);

        if (!$discount || !$discount->is_active || 
            $discount->start_date->isFuture() || 
            $discount->end_date->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive discount',
            ], 400);
        }

        $discountAmount = ($request->original_amount * $discount->percentage) / 100;
        $finalAmount = $request->original_amount - $discountAmount;

        return response()->json([
            'success' => true,
            'message' => 'Discount applied successfully',
            'data' => [
                'original_amount' => $request->original_amount,
                'discount_percentage' => $discount->percentage,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
            ],
        ]);
    }
}