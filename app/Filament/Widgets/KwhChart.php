<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardCharges;
use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KwhChart extends ChartWidget
{
    use FiltersDashboardCharges;
    use InteractsWithPageFilters;

    protected ?string $heading = 'kWh per Month & Average (kWh)';

    protected static ?int $sort = 3;

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

        $userId = Auth::id();

        $monthlyCharges = $this->applyDashboardChargeFilters(
            Charge::query()->where('charges.user_id', $userId),
            appliesDateRange: true,
        )
            ->select(DB::raw('YEAR(charges.date) as year'), DB::raw('MONTH(charges.date) as month'), DB::raw('SUM(kWh) as kWh'))
            ->groupBy(DB::raw('YEAR(charges.date)'), DB::raw('MONTH(charges.date)'))
            ->orderBy(DB::raw('YEAR(charges.date)'), 'asc')
            ->orderBy(DB::raw('MONTH(charges.date)'), 'asc')
            ->get()
            ->keyBy(fn ($charge): string => ((int) $charge->year).'-'.((int) $charge->month));

        $charges = collect($this->dashboardMonths())
            ->map(function ($month) use ($monthNames, $monthlyCharges): array {
                $charge = $monthlyCharges->get($month->year.'-'.$month->month);

                return [
                    'label' => $month->year.' '.strtolower($monthNames[$month->month]),
                    'kWh' => (float) ($charge?->kWh ?? 0),
                ];
            })
            ->all();

        $labels = array_column($charges, 'label');
        $values = array_column($charges, 'kWh');

        if (count($values) > 0) {
            $average = floor(array_sum($values) / count($values));
        } else {
            $average = 'Tidak ada data'; // atau nilai default lainnya
        }

        return [
            'datasets' => [
                [
                    'label' => 'kWh',
                    'data' => $values,
                ],
                [
                    'label' => 'Average',
                    'data' => array_fill(0, count($values), $average),
                    'borderColor' => 'rgba(255, 99, 132, 0.2)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'type' => 'line',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
