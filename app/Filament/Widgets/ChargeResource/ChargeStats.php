<?php

namespace App\Filament\Widgets\ChargeResource;

use App\Filament\Widgets\Concerns\FiltersDashboardCharges;
use App\Models\Charge;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ChargeStats extends BaseWidget
{
    use FiltersDashboardCharges;
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $query = $this->applyDashboardChargeFilters(
            Charge::query()->where('charges.user_id', Auth::id()),
        );

        // Calculate the total cost
        $totalCost = $query->sum('total_cost');
        $formattedTotalCost = 'Rp '.number_format($totalCost, 0, ',', '.');

        $startChargingNow = $query->sum('start_charging_now');
        $finishChargingNow = $query->sum('finish_charging_now');

        // Calculate the total cycle
        $totalCycle = ($finishChargingNow - $startChargingNow) / 100;
        $formattedTotalCycle = number_format($totalCycle, 0, ',', '.');

        $totalkWh = $query->sum('kWh');
        $formattedTotalkWh = number_format($totalkWh, 2, ',', '.').' kWh';

        $costPerKWh = $totalkWh > 0 ? $totalCost / $totalkWh : 0;
        $formattedCostPerKWh = 'Rp '.number_format($costPerKWh, 2, ',', '.').' /kWh';

        $kmNow = $query->sum('km_now');
        $kmBefore = $query->sum('km_before');

        $totalKm = $kmNow - $kmBefore;
        $formattedTotalKm = number_format($totalKm, 0, ',', '.').' km';

        // Calculate the cost per km
        $costPerKm = $totalKm > 0 ? $totalCost / $totalKm : 0;
        $formattedCostPerKm = 'Rp '.number_format($costPerKm, 2, ',', '.').' /km';

        // Calculate the km per kWh
        $kmPerkWh = $totalkWh > 0 ? $totalKm / $totalkWh : 0;
        $formattedkmPerkWh = number_format($kmPerkWh, 2, ',', '.').' km/kWh';

        return [
            Stat::make('km/kWh', $formattedkmPerkWh),
            Stat::make('Cost per km', $formattedCostPerKm),
            Stat::make('Cost per kWh', $formattedCostPerKWh),
            Stat::make('Total kWh', $formattedTotalkWh),
            Stat::make('Total km', $formattedTotalKm),
            Stat::make('Total Cost', $formattedTotalCost),
        ];
    }
}
