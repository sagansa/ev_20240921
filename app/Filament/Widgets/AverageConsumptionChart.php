<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardCharges;
use App\Models\Charge;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;

class AverageConsumptionChart extends ChartWidget
{
    use FiltersDashboardCharges;
    use InteractsWithPageFilters;

    protected ?string $heading = 'Consumption Trend Summary (km/kWh)';

    protected static ?int $sort = 4;

    protected int $maxSummaryPoints = 60;

    protected function getData(): array
    {
        $charges = $this->applyDashboardChargeFilters(
            Charge::query()
                ->where('charges.user_id', Auth::id())
                ->join('vehicles', 'charges.vehicle_id', '=', 'vehicles.id')
                ->join('type_vehicles', 'vehicles.type_vehicle_id', '=', 'type_vehicles.id'),
            hasVehiclesJoin: true,
        )
            ->select(
                'charges.date',
                'charges.km_now',
                'charges.km_before',
                'charges.finish_charging_before',
                'charges.start_charging_now',
                'vehicles.license_plate',
                'type_vehicles.battery_capacity',
            )
            ->orderBy('charges.date')
            ->orderBy('charges.created_at')
            ->get();

        $rows = $charges
            ->map(function ($charge): ?array {
                $mileage = (float) $charge->km_now - (float) $charge->km_before;
                $usedBatteryPercentage = (float) $charge->finish_charging_before - (float) $charge->start_charging_now;
                $usedBatteryKwh = $usedBatteryPercentage * ((float) $charge->battery_capacity / 100);

                if ($mileage <= 0 || $usedBatteryKwh <= 0) {
                    return null;
                }

                $dateLabel = Carbon::parse($charge->date)->format('d M');

                return [
                    'date_label' => $dateLabel,
                    'label' => $dateLabel.' - '.$charge->license_plate,
                    'consumption' => $mileage / $usedBatteryKwh,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $summaryRows = $this->summarizeRows($rows);

        return [
            'datasets' => [
                [
                    'label' => 'Average km/kWh',
                    'data' => array_column($summaryRows, 'consumption'),
                ],
                [
                    'label' => 'Trend',
                    'data' => $this->linearTrendLine(array_column($summaryRows, 'consumption')),
                    'borderColor' => 'rgba(255, 99, 132, 0.9)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.15)',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => array_column($summaryRows, 'label'),
        ];
    }

    protected function summarizeRows(array $rows): array
    {
        $count = count($rows);

        if ($count <= $this->maxSummaryPoints) {
            return $rows;
        }

        $chunkSize = (int) ceil($count / $this->maxSummaryPoints);

        return collect($rows)
            ->chunk($chunkSize)
            ->map(function ($chunk): array {
                $values = $chunk->pluck('consumption')->all();
                $first = $chunk->first();
                $last = $chunk->last();
                $dateRange = $first['date_label'] === $last['date_label']
                    ? $first['date_label']
                    : $first['date_label'].' - '.$last['date_label'];

                return [
                    'label' => $dateRange.' ('.$chunk->count().'x)',
                    'consumption' => array_sum($values) / count($values),
                ];
            })
            ->values()
            ->all();
    }

    protected function linearTrendLine(array $values): array
    {
        $count = count($values);

        if ($count < 2) {
            return $values;
        }

        $sumX = array_sum(range(0, $count - 1));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumXX = 0;

        foreach ($values as $index => $value) {
            $sumXY += $index * $value;
            $sumXX += $index * $index;
        }

        $denominator = ($count * $sumXX) - ($sumX * $sumX);

        if ($denominator === 0) {
            return $values;
        }

        $slope = (($count * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $count;

        return collect(range(0, $count - 1))
            ->map(fn (int $index): float => $intercept + ($slope * $index))
            ->all();
    }

    protected function getType(): string
    {
        return 'line';
    }
}
