<?php

namespace App\Filament\Widgets;

use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LossesChart extends ChartWidget
{
    protected static ?string $heading = 'Average Charging Losses (%)';

    // protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

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
                DB::raw('SUM((finish_charging_now - start_charging_now) * type_vehicles.battery_capacity / 100) as total_charge_battery'),
                DB::raw('SUM(kWh) as total_kWh'),
                DB::raw('type_vehicles.battery_capacity as battery_capacity')
            )
            ->groupBy('providers.name')
            ->get();

        $data = [];
        $labels = [];

        foreach ($charges as $charge) {
            $average_loss = (($charge->total_kWh / $charge->total_charge_battery) - 1) * 100;
            if ($average_loss > 0) {
                $data[] = $average_loss;
                $labels[] = $charge->provider;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Average Losses',
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
