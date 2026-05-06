<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

trait FiltersDashboardCharges
{
    protected function applyDashboardChargeFilters(
        Builder $query,
        bool $hasVehiclesJoin = false,
        bool $appliesDateRange = false,
    ): Builder {
        $vehicleId = $this->dashboardVehicleId();
        $typeVehicleId = $this->dashboardTypeVehicleId();
        $startDate = $this->dashboardStartDate();
        $endDate = $this->dashboardEndDate();

        return $query
            ->when(
                $vehicleId,
                fn (Builder $query, string $vehicleId): Builder => $query->where('charges.vehicle_id', $vehicleId),
            )
            ->when(
                $typeVehicleId,
                function (Builder $query, string $typeVehicleId) use ($hasVehiclesJoin): Builder {
                    if ($hasVehiclesJoin) {
                        return $query->where('vehicles.type_vehicle_id', $typeVehicleId);
                    }

                    return $query->whereHas(
                        'vehicle',
                        fn (Builder $query): Builder => $query->where('type_vehicle_id', $typeVehicleId),
                    );
                },
            )
            ->when(
                $appliesDateRange,
                fn (Builder $query): Builder => $query->whereBetween('charges.date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]),
            );
    }

    protected function dashboardTypeVehicleId(): ?string
    {
        $typeVehicleId = $this->filters['type_vehicle_id'] ?? null;

        return filled($typeVehicleId) ? (string) $typeVehicleId : null;
    }

    protected function dashboardVehicleId(): ?string
    {
        $vehicleId = $this->filters['vehicle_id'] ?? null;

        return filled($vehicleId) ? (string) $vehicleId : null;
    }

    protected function dashboardStartDate(): Carbon
    {
        $startDate = $this->filters['startDate'] ?? null;

        return filled($startDate)
            ? Carbon::parse($startDate)->startOfDay()
            : now()->subMonthsNoOverflow(12)->startOfMonth();
    }

    protected function dashboardEndDate(): Carbon
    {
        $endDate = $this->filters['endDate'] ?? null;

        return filled($endDate)
            ? Carbon::parse($endDate)->endOfDay()
            : now()->subMonthNoOverflow()->endOfMonth();
    }

    protected function dashboardMonths(): array
    {
        $startDate = $this->dashboardStartDate()->startOfMonth();
        $endDate = $this->dashboardEndDate()->startOfMonth();

        return collect(CarbonPeriod::create($startDate, '1 month', $endDate))
            ->map(fn (Carbon $month): Carbon => $month->copy())
            ->all();
    }
}
