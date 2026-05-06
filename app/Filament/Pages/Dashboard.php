<?php

namespace App\Filament\Pages;

use App\Models\TypeVehicle;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('type_vehicle_id')
                            ->label('Type Vehicle')
                            ->options(function () {
                                return TypeVehicle::query()
                                    ->with(['modelVehicle.brandVehicle'])
                                    ->whereHas('vehicles', function (Builder $query) {
                                        $query->where('user_id', Auth::id());
                                    })
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function (TypeVehicle $typeVehicle): array {
                                        $brand = $typeVehicle->modelVehicle?->brandVehicle?->name;
                                        $model = $typeVehicle->modelVehicle?->name;
                                        $label = collect([$brand, $model, $typeVehicle->name])
                                            ->filter()
                                            ->implode(' - ');

                                        return [$typeVehicle->id => $label ?: $typeVehicle->name];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('vehicle_id', null))
                            ->placeholder('All Type Vehicles'),
                        Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->options(function (Get $get) {
                                return Vehicle::query()
                                    ->with(['brandVehicle', 'modelVehicle', 'typeVehicle'])
                                    ->where('user_id', Auth::id())
                                    ->when(
                                        $get('type_vehicle_id'),
                                        fn (Builder $query, $typeVehicleId): Builder => $query->where('type_vehicle_id', $typeVehicleId),
                                    )
                                    ->orderBy('license_plate')
                                    ->get()
                                    ->mapWithKeys(function (Vehicle $vehicle): array {
                                        $label = collect([
                                            $vehicle->license_plate,
                                            $vehicle->brandVehicle?->name,
                                            $vehicle->modelVehicle?->name,
                                            $vehicle->typeVehicle?->name,
                                        ])
                                            ->filter()
                                            ->implode(' - ');

                                        return [$vehicle->id => $label ?: $vehicle->id];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('All Vehicles'),
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->default(now()->subMonthsNoOverflow(12)->startOfMonth())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->default(now()->subMonthNoOverflow()->endOfMonth())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->minDate(fn (Get $get) => $get('startDate') ?: now()->subMonthsNoOverflow(12)->startOfMonth())
                            ->maxDate(now()),
                    ])
                    ->columns(4),
            ]);
    }
}
