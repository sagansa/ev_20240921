<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardCharges;
use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CostByVehicleChart extends ChartWidget
{
    use FiltersDashboardCharges;
    use InteractsWithPageFilters;

    protected ?string $heading = 'Charging Cost by Vehicle';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $charges = $this->applyDashboardChargeFilters(
            Charge::query()->where('charges.user_id', Auth::id())
                ->join('vehicles', 'charges.vehicle_id', '=', 'vehicles.id')
                ->leftJoin('brand_vehicles', 'vehicles.brand_vehicle_id', '=', 'brand_vehicles.id')
                ->leftJoin('model_vehicles', 'vehicles.model_vehicle_id', '=', 'model_vehicles.id')
                ->leftJoin('type_vehicles', 'vehicles.type_vehicle_id', '=', 'type_vehicles.id'),
            hasVehiclesJoin: true,
        )
            ->select(
                DB::raw('vehicles.license_plate as license_plate'),
                DB::raw('brand_vehicles.name as brand_name'),
                DB::raw('model_vehicles.name as model_name'),
                DB::raw('type_vehicles.name as type_name'),
                DB::raw('SUM(charges.total_cost) as total_cost'),
            )
            ->groupBy(
                'vehicles.id',
                'vehicles.license_plate',
                'brand_vehicles.name',
                'model_vehicles.name',
                'type_vehicles.name',
            )
            ->orderByDesc('total_cost')
            ->get();

        $labels = [];
        $data = [];

        foreach ($charges as $charge) {
            $labels[] = collect([
                $charge->license_plate,
                $charge->brand_name,
                $charge->model_name,
                $charge->type_name,
            ])
                ->filter()
                ->implode(' - ');

            $data[] = (float) $charge->total_cost;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Cost',
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
