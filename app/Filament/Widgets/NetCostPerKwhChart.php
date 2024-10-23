<?php

namespace App\Filament\Widgets;

use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NetCostPerKwhChart extends ChartWidget
{
    protected static ?string $heading = 'Net Average Cost per kWh (Rp/kWh)';

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
            ->where('charges.total_cost', '>', 0) // Add this line to filter out zero total cost
            ->select(
                DB::raw('providers.name as provider'),
                DB::raw('SUM((finish_charging_now - start_charging_now) * type_vehicles.battery_capacity / 100) as total_charge_battery'),
                DB::raw('type_vehicles.battery_capacity as battery_capacity'),
                DB::raw('SUM(total_cost) as total_cost'),
            )
            ->groupBy('providers.name')
            ->get();

        $data = [];
        $labels = [];

        foreach ($charges as $charge) {
            if ($charge->total_cost > 0) {
                $net_cost_per_kWh = $charge->total_cost / $charge->total_charge_battery;
                $data[] = $net_cost_per_kWh;
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
