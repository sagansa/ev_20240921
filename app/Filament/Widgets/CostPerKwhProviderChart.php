<?php

namespace App\Filament\Widgets;

use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CostPerKwhProviderChart extends ChartWidget
{
    protected static ?string $heading = 'Gross Average Cost per kWh (Rp/kWh)';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $userId = Auth::id();

        $charges = Charge::where('charges.user_id', $userId)
            ->where('charges.is_kwh_measured', 1)
            ->join('vehicles', 'charges.vehicle_id', '=', 'vehicles.id')
            ->join('type_vehicles', 'vehicles.type_vehicle_id', '=', 'type_vehicles.id')
            ->join('charger_locations', 'charges.charger_location_id', '=', 'charger_locations.id')
            ->join('providers', 'charger_locations.provider_id', '=', 'providers.id')
            ->where('providers.status', '1')
            ->select(
                DB::raw('providers.name as provider'),
                DB::raw('SUM(CASE WHEN total_cost > 0 THEN kWh ELSE 0 END) as total_kWh'),
                DB::raw('SUM(total_cost) as total_cost')
            )
            ->groupBy('providers.name')
            ->get();

        $data = [];
        $labels = [];

        foreach ($charges as $charge) {
            if ($charge->total_kWh > 0) {
                $cost_per_kWh = $charge->total_cost / $charge->total_kWh;
                $data[] = $cost_per_kWh;
                $labels[] = $charge->provider;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Average cost per kWh',
                    'data' => $data,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
