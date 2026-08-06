<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FuelPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historical fuel prices (fuel_prices) for the mobile dashboard — used by the
 * client to compute the BBM cost estimate per session date (BBM savings).
 */
class FuelPriceController extends Controller
{
    /**
     * Fuel price list ordered by newest date. Old charging sessions use the
     * price effective on the session date (latest price with date <= session).
     */
    public function index(Request $request): JsonResponse
    {
        $fuelName = $request->input('fuel_name');
        $query = FuelPrice::query();

        if ($request->filled('fuel_name')) {
            $query->where('fuel_name', $fuelName);
        }

        $prices = $query
            ->orderByDesc('effective_date')
            ->get()
            ->map(fn (FuelPrice $price) => [
                'effective_date' => $price->effective_date?->toDateString(),
                'fuel_name' => $price->fuel_name,
                'price_per_liter' => round((float) $price->price_per_liter, 0),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Fuel prices retrieved successfully',
            'data' => $prices,
        ]);
    }
}
