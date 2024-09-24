<?php

namespace App\Filament\Resources\Panel;

use Filament\Tables;
use App\Models\Charge;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\ChargeResource\Pages;
use App\Models\Charger;
use App\Models\Vehicle;
use App\Models\ChargerLocation;
use Filament\Forms\Components\Toggle;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ToggleColumn;
use App\Filament\Widgets\ChargeResource\ChargeStats;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.charges.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.charges.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.charges.collectionTitle');
    }

    public static function getWidgets(): array
    {
        return [
            ChargeStats::class,
        ];
    }

    public static function form(Form $form): Form
    {

        return $form->schema([
            Section::make('Start Charging')->schema([
                Grid::make(['default' => 1])->schema([
                    FileUpload::make('image')
                        ->rules(['image'])
                        ->nullable()
                        ->maxSize(1024)
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']),

                    Grid::make(['default' => 1])->schema([
                        Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->required()
                            ->options(function () {
                                return Vehicle::where('user_id', Auth::id())
                                    ->where('status', 1)
                                    ->pluck('license_plate', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('km_before', ChargeResource::getLatestKmNowForVehicle($state));
                                    $set('finish_charging_before', ChargeResource::getLatestChargingNowForVehicle($state));
                                }
                            }),

                        DatePicker::make('date')
                            ->default('today')
                            ->native(false)
                            ->required(),

                        Select::make('charger_location_id')
                            ->label('Charger Location')
                            ->required()
                            // ->relationship('chargerLocation', 'name')
                            ->relationship(
                                name: 'chargerLocation',
                                modifyQueryUsing: fn (Builder $query) => $query->where('status','<>', '3')->orderBy('name', 'asc'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (ChargerLocation $record) => "{$record->charger_location_name}")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('charger_id', null);
                            }),

                        Select::make('charger_id')
                            ->label('Charger')
                            ->reactive()
                            ->options(function (callable $get) {
                                $chargerLocationId = $get('charger_location_id');
                                return Charger::all()->where('charger_location_id', $chargerLocationId)->pluck('charger_name', 'id')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),

                        TextInput::make('km_now')
                            ->label('km start charging')
                            ->mask(RawJs::make('$money($input)'))
                            ->required()
                            ->minValue(0)
                            ->numeric()
                            ->suffix('km')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('km_now', preg_replace('/[^\d\.]/', '', $state));
                            }),

                        TextInput::make('km_before')
                            ->label('km data before')
                            ->mask(RawJs::make('$money($input)'))
                            ->required()
                            ->minValue(0)
                            ->numeric()
                            ->suffix('km')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('km_before', preg_replace('/[^\d\.]/', '', $state));
                            }),

                        TextInput::make('start_charging_now')
                            ->label('Percentage battery start')
                            ->required()
                            ->minValue(0)
                            ->numeric()
                            ->suffix('%'),


                    ])
                    ->columns(3)
                ]),
            ]),

            Toggle::make('is_finish_charging')
                ->label('Is the charging finish?')
                ->default(false)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('finish_charging_section', $state);
                }),

            Section::make('Finish Charging')
                ->visible(fn ($get) => $get('is_finish_charging'))
                ->schema([
                    Grid::make(['default' => 1])->schema([
                        TextInput::make('finish_charging_now')
                            ->label('Percentage battery finish')
                            ->requiredWith('is_finish_charging')
                            ->minValue(0)
                            ->numeric()
                            ->maxValue(100)
                            ->suffix('%'),

                        TextInput::make('finish_charging_before')
                            ->label('Percentage battery finish before')
                            ->requiredWith('is_finish_charging')
                            ->minValue(0)
                            ->numeric()
                            ->maxValue(100)
                            ->suffix('%'),

                        TextInput::make('parking')
                            ->requiredWith('is_finish_charging')
                            // ->mask(RawJs::make('$money($input)'))
                            ->default(0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('Rp'),

                        Toggle::make('is_kwh_measured')
                            ->label('Is kWh measured?')
                            ->default(false),

                        TextInput::make('kWh')
                            ->label('kWh')
                            ->requiredWith('is_finish_charging')
                            ->minValue(0)
                            ->numeric()
                            ->reactive()
                            ->suffix('kWh'),

                        TextInput::make('street_lighting_tax')
                            ->label('PPJ')
                            ->requiredWith('is_finish_charging')
                            // ->mask(RawJs::make('$money($input)'))
                            ->default(0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('value_added_tax')
                            ->label('VAT')
                            ->requiredWith('is_finish_charging')
                            // ->mask(RawJs::make('$money($input)'))
                            ->default(0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('admin_cost')
                            ->requiredWith('is_finish_charging')
                            // ->mask(RawJs::make('$money($input)'))
                            ->default(0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('Rp'),

                        TextInput::make('total_cost')
                            ->requiredWith('is_finish_charging')
                            // ->mask(RawJs::make('$money($input)'))
                            ->default(0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->columns(3),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');

                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->poll('60s')
            ->columns([

                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('vehicle.license_plate'),

                TextColumn::make('date')
                    ->sortable()
                    ->date(),

                TextColumn::make('chargerLocation.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chargerLocation.provider.name')
                    ->label('Provider')
                    ->sortable(),

                TextColumn::make('charger.charger_name'),

                TextColumn::make('km_now')
                    ->label('km now')
                    ->sortable()
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('km_before')
                    ->label('km before')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin'))
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('start_charging_now')
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),
                    // ->summarize(Sum::make()),

                TextColumn::make('finish_charging_now')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    // ->summarize(Sum::make()),

                TextColumn::make('finish_charging_before')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin'))->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parking')
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->prefix('Rp ')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_kwh_measured')
                    ->label('kWh measured')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('kWh')
                    ->label('kWh')
                    ->sortable()
                    ->numeric(
                        decimalPlaces: 2,
                        thousandsSeparator: '.',
                        decimalSeparator: ',',
                        )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Sum::make()
                        ->label('')
                        ->numeric(
                            decimalPlaces: 2,
                            thousandsSeparator: '.',
                            decimalSeparator: ',',
                            )
                        ->suffix(' kWh')),

                TextColumn::make('street_lighting_tax')
                    ->label('PPJ')
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->prefix('Rp ')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('value_added_tax')
                    ->label('PPN')
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->prefix('Rp ')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('admin_cost')
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->prefix('Rp ')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_cost')
                    ->sortable()
                    ->numeric(
                        thousandsSeparator: '.'
                    )
                    ->prefix('Rp ')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                TextColumn::make('losses')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->getStateUsing(function ($record) {
                        $batteryCapacity = $record->vehicle->typeVehicle->battery_capacity;
                        $startCharge = $record->start_charging_now;
                        $finishCharge = $record->finish_charging_now;
                        $kWh = $record->kWh;
                        $isKwhMeasured = $record->is_kwh_measured; // Add this line

                        if (!$isKwhMeasured) {
                            return 0; // If is_kwh_measured is false, set losses to 0
                        }
                        $chargeBatteryCapacity = ($finishCharge - $startCharge) * ($batteryCapacity/100);
                        $losses = $chargeBatteryCapacity > 0 ? (($kWh / $chargeBatteryCapacity) - 1) * 100 : 0;
                        return $losses;
                    }),

                TextColumn::make('Consumption')
                    ->suffix('km/kWh')
                    ->numeric(decimalPlaces: 2)
                    ->getStateUsing(function ($record) {
                        $finishChargingBefore = $record->finish_charging_before;
                        $startChargingNow = $record->start_charging_now;
                        $usedBattery = $finishChargingBefore - $startChargingNow;

                        $batteryCapacity = $record->vehicle->typeVehicle->battery_capacity;

                        $usedkWh = $usedBattery * $batteryCapacity;

                        $kmNow = $record->km_now;
                        $kmBefore = $record->km_before;
                        $miliage = $kmNow - $kmBefore;

                        $consumption = $usedkWh > 0 ? $miliage / $usedkWh * 100 : 0;
                        return $consumption;
                    }),

                TextColumn::make('cost_per_kwh')
                    ->label('Rp / kWh')
                    ->getStateUsing(function ($record) {
                        return $record->kWh > 0 ? $record->total_cost / $record->kWh : 0;
                    })
                    ->numeric(thousandsSeparator: '.')
                    ->prefix('Rp ')
                    ->suffix(' /kWh'),

                TextColumn::make('user.name')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin')), // Kondisi visibilitas,

            ])
            ->filters([
                    SelectFilter::make('vehicle')
                        ->relationship('vehicle','license_plate'),
                    SelectFilter::make('current_charger')
                        ->relationship('charger.currentCharger', 'name')
                        ->label('Current'),
                    SelectFilter::make('provider')
                        ->relationship('chargerLocation.provider', 'name')
                        ->label('Provider'),
                    SelectFilter::make('charger_location_id')
                        ->searchable()
                        ->label('Location')
                        ->relationship('chargerLocation', 'name'),
                    SelectFilter::make('is_kwh_measured')
                        ->label('kWh Measured')
                        ->options([
                            '1' => 'is measured',
                            '0' => 'No',
                        ]),
                    Filter::make('Date')
                        ->form([
                            DatePicker::make('date_from'),
                            DatePicker::make('date_until'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['date_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                                )
                                ->when(
                                    $data['date_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                                );
                        })

                    ],
                    // layout: FiltersLayout::AboveContent
                )
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCharges::route('/'),
            'create' => Pages\CreateCharge::route('/create'),
            'view' => Pages\ViewCharge::route('/{record}'),
            'edit' => Pages\EditCharge::route('/{record}/edit'),
        ];
    }

    public static function getLatestKmNowForVehicle($vehicleId)
    {
        $latestKm = Charge::where('vehicle_id', $vehicleId)
            ->latest('date')
            ->first();

        return $latestKm ? $latestKm->km_now : 0;
    }

    public static function getLatestChargingNowForVehicle($vehicleId)
    {
        $latestCharge = Charge::where('vehicle_id', $vehicleId)
            ->latest('date')
            ->first();

        return $latestCharge ? $latestCharge->finish_charging_now : 0;
    }
}
