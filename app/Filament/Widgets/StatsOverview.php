<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Charge;
use App\Models\DiscountHomeCharging;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
            Carbon::parse($this->filters['startDate']) :
            null;

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate']) :
            now();

        $discount = DiscountHomeCharging::where('user_id', Auth::id());
        $charge = Charge::where('user_id', Auth::id());

        if ($startDate) {
            $charge->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $charge->whereDate('date', '<=', $endDate);
        }

        // Calculate the net total cost
        $totalCost = $charge->sum('total_cost');
        $discountTotal = $discount->sum('discount_total');
        $netTotalCost = $totalCost - $discountTotal;
        $formattedNetTotalCost = 'Rp ' . number_format($netTotalCost, 0, ',', '.');

        $startChargingNow = $charge->sum('start_charging_now');
        $finishChargingNow = $charge->sum('finish_charging_now');

        // Calculate the total cycle
        $totalCycle = ($finishChargingNow - $startChargingNow) / 100;
        $formattedTotalCycle = number_format($totalCycle, 0, ',', '.');

        $totalkWh = $charge->sum('kWh');
        $formattedTotalkWh = number_format($totalkWh, 2, ',', '.') . ' kWh';

        $costPerKWh = $totalkWh > 0 ? $netTotalCost / $totalkWh : 0;
        $formattedCostPerKWh = 'Rp ' . number_format($costPerKWh, 2, ',', '.') . ' /kWh';

        $kmNow = $charge->sum('km_now');
        $kmBefore = $charge->sum('km_before');

        $totalKm = $kmNow - $kmBefore;
        $formattedTotalKm = number_format($totalKm, 0, ',', '.') . ' km';

        // Calculate the cost per km
        $costPerKm = $totalKm > 0 ? $netTotalCost / $totalKm : 0;
        $formattedCostPerKm = 'Rp ' . number_format($costPerKm, 2, ',', '.') . ' /km';

        // Calculate the km per kWh
        $kmPerkWh = $totalkWh > 0 ? $totalKm / $totalkWh : 0;
        $formattedkmPerkWh = number_format($kmPerkWh, 2, ',', '.') . ' km/kWh';

        return [
            Stat::make('km/kWh', $formattedkmPerkWh),
            Stat::make('Cost per km', $formattedCostPerKm),
            Stat::make('Cost per kWh', $formattedCostPerKWh),
            Stat::make('Total kWh', $formattedTotalkWh),
            Stat::make('Total km', $formattedTotalKm),
            Stat::make('Total Cost', $formattedNetTotalCost),
        ];
    }

    protected function getFormSchema(): array
    {
        return [];
    }
}
