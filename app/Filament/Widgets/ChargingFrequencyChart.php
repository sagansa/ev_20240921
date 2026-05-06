<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardCharges;
use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChargingFrequencyChart extends ChartWidget
{
    use FiltersDashboardCharges;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Charging Frequency per Month';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        $monthlyCharges = $this->applyDashboardChargeFilters(
            Charge::query()->where('charges.user_id', Auth::id()),
            appliesDateRange: true,
        )
            ->select(
                DB::raw('YEAR(charges.date) as year'),
                DB::raw('MONTH(charges.date) as month'),
                DB::raw('COUNT(*) as total_charges'),
            )
            ->groupBy(DB::raw('YEAR(charges.date)'), DB::raw('MONTH(charges.date)'))
            ->orderBy(DB::raw('YEAR(charges.date)'), 'asc')
            ->orderBy(DB::raw('MONTH(charges.date)'), 'asc')
            ->get()
            ->keyBy(fn ($charge): string => ((int) $charge->year).'-'.((int) $charge->month));

        $rows = collect($this->dashboardMonths())
            ->map(function ($month) use ($monthNames, $monthlyCharges): array {
                $charge = $monthlyCharges->get($month->year.'-'.$month->month);

                return [
                    'label' => $month->year.' '.strtolower($monthNames[$month->month]),
                    'total_charges' => (int) ($charge?->total_charges ?? 0),
                ];
            })
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Charging sessions',
                    'data' => array_column($rows, 'total_charges'),
                ],
            ],
            'labels' => array_column($rows, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
