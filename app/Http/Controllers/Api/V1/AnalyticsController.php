<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Charge;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    /**
     * Get charging patterns for the authenticated user.
     */
    public function chargingPatterns(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $query = $user->charges()->with(['vehicle', 'chargerLocation']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $charges = $query->get();

        // Calculate patterns
        $totalCharges = $charges->count();
        $totalCost = $charges->sum('total_cost');
        $totalKwh = $charges->sum('kWh');
        $totalDistance = $charges->sum(function ($charge) {
            return max(0, $charge->km_now - $charge->km_before);
        });

        // Group by month
        $monthlyPatterns = $charges
            ->groupBy(function ($charge) {
                return Carbon::parse($charge->date)->format('Y-m');
            })
            ->map(function ($chargesInMonth) {
                return [
                    'count' => $chargesInMonth->count(),
                    'total_cost' => $chargesInMonth->sum('total_cost'),
                    'total_kwh' => $chargesInMonth->sum('kWh'),
                    'avg_cost' => $chargesInMonth->avg('total_cost'),
                    'avg_kwh' => $chargesInMonth->avg('kWh'),
                ];
            });

        // Group by provider
        $byProvider = $charges
            ->groupBy('chargerLocation.provider.name')
            ->map(function ($chargesByProvider) {
                return [
                    'count' => $chargesByProvider->count(),
                    'total_cost' => $chargesByProvider->sum('total_cost'),
                    'total_kwh' => $chargesByProvider->sum('kWh'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Charging patterns retrieved successfully',
            'data' => [
                'summary' => [
                    'total_charges' => $totalCharges,
                    'total_cost' => $totalCost,
                    'total_kwh' => $totalKwh ? round($totalKwh, 2) : 0,
                    'total_distance' => $totalDistance,
                    'avg_cost_per_session' => $totalCharges > 0 ? round($totalCost / $totalCharges, 2) : 0,
                    'avg_kwh_per_session' => $totalCharges > 0 ? round($totalKwh / $totalCharges, 2) : 0,
                ],
                'monthly_patterns' => $monthlyPatterns,
                'by_provider' => $byProvider,
            ],
        ]);
    }

    /**
     * Get cost analysis for the authenticated user.
     */
    public function costAnalysis(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $query = $user->charges()->with(['vehicle', 'chargerLocation']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $charges = $query->get();

        // Calculate cost breakdown
        $totalCost = $charges->sum('total_cost');
        $totalStreetLightingTax = $charges->sum('street_lighting_tax');
        $totalValueAddedTax = $charges->sum('value_added_tax');
        $totalAdminCost = $charges->sum('admin_cost');
        $totalBaseCost = $totalCost - ($totalStreetLightingTax + $totalValueAddedTax + $totalAdminCost);

        // Cost per kWh
        $totalKwh = $charges->sum('kWh');
        $costPerKwh = $totalKwh > 0 ? $totalCost / $totalKwh : 0;

        // Cost by provider
        $byProvider = $charges
            ->groupBy('chargerLocation.provider.name')
            ->map(function ($chargesByProvider) {
                return [
                    'total_cost' => $chargesByProvider->sum('total_cost'),
                    'total_kwh' => $chargesByProvider->sum('kWh'),
                    'avg_cost_per_kwh' => $chargesByProvider->sum('kWh') > 0 
                        ? $chargesByProvider->sum('total_cost') / $chargesByProvider->sum('kWh') 
                        : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Cost analysis retrieved successfully',
            'data' => [
                'summary' => [
                    'total_cost' => $totalCost,
                    'base_cost' => $totalBaseCost,
                    'street_lighting_tax' => $totalStreetLightingTax,
                    'value_added_tax' => $totalValueAddedTax,
                    'admin_cost' => $totalAdminCost,
                    'total_kwh' => $totalKwh ? round($totalKwh, 2) : 0,
                    'cost_per_kwh' => round($costPerKwh, 2),
                ],
                'breakdown_by_provider' => $byProvider,
            ],
        ]);
    }

    /**
     * Generate custom reports.
     */
    public function reports(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'report_type' => 'required|in:summary,detailed,provider_comparison,vehicle_comparison',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $query = $user->charges()->with(['vehicle', 'chargerLocation']);

        // Apply date range filter
        $query->whereDate('date', '>=', $request->date_from)
              ->whereDate('date', '<=', $request->date_to);

        // Apply vehicle filter if specified
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $charges = $query->get();

        // Generate report based on type
        switch ($request->report_type) {
            case 'summary':
                return $this->generateSummaryReport($charges);
            case 'detailed':
                return $this->generateDetailedReport($charges, $request);
            case 'provider_comparison':
                return $this->generateProviderComparisonReport($charges);
            case 'vehicle_comparison':
                return $this->generateVehicleComparisonReport($user, $request);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid report type',
                ], 400);
        }
    }

    /**
     * Generate summary report.
     */
    private function generateSummaryReport($charges)
    {
        $totalCharges = $charges->count();
        $totalCost = $charges->sum('total_cost');
        $totalKwh = $charges->sum('kWh');
        $totalDistance = $charges->sum(function ($charge) {
            return max(0, $charge->km_now - $charge->km_before);
        });

        return response()->json([
            'success' => true,
            'message' => 'Summary report generated successfully',
            'data' => [
                'report_type' => 'summary',
                'total_charges' => $totalCharges,
                'total_cost' => $totalCost,
                'total_kwh' => $totalKwh ? round($totalKwh, 2) : 0,
                'total_distance' => $totalDistance,
                'avg_cost_per_session' => $totalCharges > 0 ? round($totalCost / $totalCharges, 2) : 0,
                'avg_kwh_per_session' => $totalCharges > 0 ? round($totalKwh / $totalCharges, 2) : 0,
                'avg_distance_per_session' => $totalCharges > 0 ? round($totalDistance / $totalCharges, 2) : 0,
                'cost_per_kwh' => $totalKwh > 0 ? round($totalCost / $totalKwh, 2) : 0,
            ],
        ]);
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport($charges, $request)
    {
        $chargesWithDetails = $charges->map(function ($charge) {
            return [
                'id' => $charge->id,
                'date' => $charge->date,
                'vehicle' => $charge->vehicle->name ?? 'Unknown',
                'location' => $charge->chargerLocation->name ?? 'Unknown',
                'provider' => $charge->chargerLocation->provider->name ?? 'Unknown',
                'kwh' => $charge->kWh,
                'cost' => $charge->total_cost,
                'distance_traveled' => max(0, $charge->km_now - $charge->km_before),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Detailed report generated successfully',
            'data' => [
                'report_type' => 'detailed',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'charges' => $chargesWithDetails,
            ],
        ]);
    }

    /**
     * Generate provider comparison report.
     */
    private function generateProviderComparisonReport($charges)
    {
        $byProvider = $charges
            ->groupBy('chargerLocation.provider.name')
            ->map(function ($chargesByProvider) {
                return [
                    'count' => $chargesByProvider->count(),
                    'total_cost' => $chargesByProvider->sum('total_cost'),
                    'total_kwh' => $chargesByProvider->sum('kWh'),
                    'avg_cost_per_session' => $chargesByProvider->avg('total_cost'),
                    'avg_kwh' => $chargesByProvider->avg('kWh'),
                    'avg_cost_per_kwh' => $chargesByProvider->sum('kWh') > 0 
                        ? $chargesByProvider->sum('total_cost') / $chargesByProvider->sum('kWh') 
                        : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Provider comparison report generated successfully',
            'data' => [
                'report_type' => 'provider_comparison',
                'providers' => $byProvider,
            ],
        ]);
    }

    /**
     * Generate vehicle comparison report.
     */
    private function generateVehicleComparisonReport($user, $request)
    {
        $vehicles = $user->vehicles;

        $vehicleStats = $vehicles->map(function ($vehicle) use ($request) {
            $charges = $vehicle->charges()
                ->whereDate('date', '>=', $request->date_from)
                ->whereDate('date', '<=', $request->date_to)
                ->get();

            return [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'charges_count' => $charges->count(),
                'total_cost' => $charges->sum('total_cost'),
                'total_kwh' => $charges->sum('kWh'),
                'total_distance' => $charges->sum(function ($charge) {
                    return max(0, $charge->km_now - $charge->km_before);
                }),
                'avg_cost_per_session' => $charges->count() > 0 ? $charges->avg('total_cost') : 0,
                'avg_kwh' => $charges->count() > 0 ? $charges->avg('kWh') : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Vehicle comparison report generated successfully',
            'data' => [
                'report_type' => 'vehicle_comparison',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'vehicles' => $vehicleStats,
            ],
        ]);
    }

    /**
     * Get visitor profiles (for admin users only).
     */
    public function visitorProfiles(Request $request)
    {
        // Only admin users can access visitor profiles
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // This would typically require a separate VisitorProfile model
        // which is not currently implemented in the system
        // Placeholder implementation
        
        return response()->json([
            'success' => false,
            'message' => 'Visitor profile tracking is not implemented in current system',
        ], 501);
    }
}