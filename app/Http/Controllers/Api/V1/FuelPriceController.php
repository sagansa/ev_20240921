<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FuelPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riwayat harga BBM (fuel_prices) untuk dashboard mobile — dipakai client
 * menghitung estimasi biaya BBM per tanggal sesi (fitur "penghematan BBM").
 */
class FuelPriceController extends Controller
{
    /**
     * Daftar harga BBM urut tanggal terbaru. Sesi charging lama memakai harga
     * yang berlaku pada tanggal sesi (harga terakhir dengan tanggal <= sesi).
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
