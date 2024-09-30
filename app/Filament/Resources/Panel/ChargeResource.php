<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\CurrencyTextInput;
use App\Filament\Forms\DecimalTextInput;
use App\Filament\Forms\NominalTextInput;
use App\Filament\Forms\PercentTextInput;
use App\Filament\Forms\TodayDatePicker;
use Filament\Tables;
use App\Models\Charge;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
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
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ToggleColumn;
use App\Filament\Widgets\ChargeResource\ChargeStats;
use Filament\Forms\Components\Group;

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

            Group::make()->schema([
                Section::make('Start Charging')->schema([

                    Grid::make(['default' => 1])->schema([
                        FileUpload::make('image_start')
                        ->rules(['image'])
                        ->nullable()
                        ->openable()
                        ->maxSize(1024)
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('images/charge')
                        ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']),
                    ]),

                    BaseSelect::make('vehicle_id')
                        ->label('Vehicle')
                        ->options(function () {
                            return Vehicle::where('user_id', Auth::id())
                                ->where('status', 1)
                                ->pluck('license_plate', 'id');
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('km_before', ChargeResource::getLatestKmNowForVehicle($state));
                                $set('finish_charging_before', ChargeResource::getLatestChargingNowForVehicle($state));
                            }
                        }),

                    TodayDatePicker::make('date'),

                    BaseSelect::make('charger_location_id')
                        ->label('Charger Location')
                        ->relationship(
                            name: 'chargerLocation',
                            modifyQueryUsing: fn (Builder $query) => $query->where('status','<>', '3')->orderBy('name', 'asc'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (ChargerLocation $record) => "{$record->charger_location_name}")
                        ->searchable()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('charger_id', null);
                        }),

                    BaseSelect::make('charger_id')
                        ->label('Charger')
                        ->reactive()
                        ->options(function (callable $get) {
                            $chargerLocationId = $get('charger_location_id');
                            return Charger::all()->where('charger_location_id', $chargerLocationId)->pluck('charger_name', 'id')->toArray();
                        })
                        ->searchable(),

                    NominalTextInput::make('km_now')
                        ->label('start charging')
                        ->suffix('km'),

                    PercentTextInput::make('start_charging_now')
                        ->label('Battery start'),

                    ])->columns(2),

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
                            FileUpload::make('image_finish')
                            ->rules(['image'])
                            ->nullable()
                            ->openable()
                            ->maxSize(1024)
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('images/charge')
                            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']),
                        ]),

                        Grid::make(['default' => 1])->schema([
                            PercentTextInput::make('finish_charging_now')
                                ->label('Battery finish now')
                                ->requiredWith('is_finish_charging'),

                            CurrencyTextInput::make('parking')
                                ->requiredWith('is_finish_charging'),

                            Toggle::make('is_kwh_measured')
                                ->label('Is kWh measured?')
                                ->default(false),

                            DecimalTextInput::make('kWh')
                                ->label('kWh')
                                ->requiredWith('is_finish_charging')
                                ->suffix('kWh'),

                            CurrencyTextInput::make('street_lighting_tax')
                                ->label('PPJ'),

                            CurrencyTextInput::make('value_added_tax')
                                ->label('VAT')
                                ->requiredWith('is_finish_charging'),

                            CurrencyTextInput::make('admin_cost')
                                ->requiredWith('is_finish_charging'),

                            CurrencyTextInput::make('total_cost')
                                ->requiredWith('is_finish_charging'),
                        ])
                        ->columns(2),
                    ]),
                ])->columnSpan(['md' => 3]),

            Section::make()->schema([

                TextInput::make('km_before')
                    ->readOnly()
                    ->label('Data before')
                    ->suffix('km')
                    ->currencyMask(thousandSeparator: '.',decimalSeparator: ',',precision: 0),

                TextInput::make('finish_charging_before')
                    ->readOnly()
                    ->label('Battery finish before')
                    ->requiredWith('is_finish_charging')
                    ->suffix('%'),

            ])->columnSpan(['md' => 1]),
        ])
        ->columns(4);
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

                ImageColumn::make('image_start')
                    ->openUrlInNewTab() // Membuka URL di tab baru
                    ->tooltip('Klik untuk membuka gambar di tab baru') // Tooltip untuk pengguna,
                    ->url(fn($record) => asset('storage/' . $record->image))
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('image_finish')
                    ->openUrlInNewTab() // Membuka URL di tab baru
                    ->tooltip('Klik untuk membuka gambar di tab baru') // Tooltip untuk pengguna,
                    ->url(fn($record) => asset('storage/' . $record->image))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vehicle.license_plate')->sortable(),

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
            ->defaultSort('created_at', 'desc');
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
            ->latest('created_at')
            ->first();

        return $latestKm ? $latestKm->km_now : 0;
    }

    public static function getLatestChargingNowForVehicle($vehicleId)
    {
        $latestCharge = Charge::where('vehicle_id', $vehicleId)
            ->latest('created_at')
            ->first();

        return $latestCharge ? $latestCharge->finish_charging_now : 0;
    }
}
