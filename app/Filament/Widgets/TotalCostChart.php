<?php

namespace App\Filament\User\Widgets;

use App\Models\Charge;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TotalCostChart extends ChartWidget
{
    protected static ?string $heading = 'Charging Cost per Month & Average (Rp)';

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

        $charges = Charge::where('user_id', $userId)
            ->select(DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(total_cost) as total_cost'))
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'))
            ->orderBy(DB::raw('YEAR(date)'), 'asc')
            ->orderBy(DB::raw('MONTH(date)'), 'asc')
            ->get()
            ->map(function ($charge) use ($monthNames) {
                return [
                    'label' => $charge->year . ' ' . strtolower($monthNames[$charge->month]),
                    'total_cost' => $charge->total_cost,
                ];
            })
            ->all();

        $labels = array_column($charges, 'label');
        $values = array_column($charges, 'total_cost');

        // $average = array_sum($values) / count($values);

        if (count($values) > 0) {
            $average = floor(array_sum($values) / count($values));
        } else {
            $average = 0; // or some other default value
        }

        return [
            'datasets' => [
                [
                    'label' => 'Charge',
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
            'scales' => [

        ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
