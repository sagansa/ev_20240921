<?php

namespace App\Filament\User\Widgets;

use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CountChargeChart extends ChartWidget
{
    protected static ?string $heading = 'Count Charge Each Provider';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $userId = Auth::id();

        $charges = Charge::where('charges.user_id', $userId)
            ->join('vehicles', 'charges.vehicle_id', '=', 'vehicles.id')
            ->join('type_vehicles', 'vehicles.type_vehicle_id', '=', 'type_vehicles.id')
            ->join('charger_locations', 'charges.charger_location_id', '=', 'charger_locations.id')
            ->join('providers', 'charger_locations.provider_id', '=', 'providers.id')
            ->where('providers.status', '1')
            ->select(
                DB::raw('providers.name as provider'),
                DB::raw('COUNT(*) as count_charge'),
            )
            ->groupBy('providers.name')
            ->get();

        $data = [];
        $labels = [];

        foreach ($charges as $charge) {
            $data[] = $charge->count_charge;
            $labels[] = $charge->provider;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Count Charge',
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
