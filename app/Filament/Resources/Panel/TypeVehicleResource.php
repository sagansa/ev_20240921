<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use App\Filament\Resources\Panel\TypeVehicleResource\Pages;
use App\Models\TypeVehicle;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TypeVehicleResource extends Resource
{
    protected static ?string $model = TypeVehicle::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-adjustments-horizontal';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Referensi Kendaraan';
    }

    public static function getModelLabel(): string
    {
        return __('crud.typeVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identitas Varian / Tipe Kendaraan')
                ->description('Pilih model induk dan tentukan nama trim / varian.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Select::make('model_vehicle_id')
                            ->required()
                            ->relationship('modelVehicle', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brandVehicle?->name} — {$record->name}")
                            ->searchable()
                            ->preload()
                            ->label('Model Kendaraan Induk'),

                        TextInput::make('name')
                            ->required()
                            ->string()
                            ->label('Nama Varian / Type')
                            ->placeholder('cth. Standard Range, Long Range, Performance, Lite, Pro'),

                        ImageFileUpload::make('image')
                            ->directory('images/type')
                            ->image()
                            ->label('Foto Tipe Kendaraan')
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Spesifikasi Daya & Pengisian (Charging)')
                ->description('Karakteristik baterai dan kompabilitas port SPKLU.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('powertrain')
                            ->label('Powertrain')
                            ->placeholder('cth. BEV, HEV')
                            ->maxLength(8),
                        TextInput::make('battery_capacity')
                            ->nullable()
                            ->numeric()
                            ->suffix('kWh')
                            ->inputMode('decimal')
                            ->label('Kapasitas Baterai')
                            ->placeholder('cth. 26.7, 50.6, 61.4, 82.5'),

                        Select::make('type_charger')
                            ->required()
                            ->multiple()
                            ->searchable()
                            ->options([
                                '1' => 'CCS2 (DC Fast Charging)',
                                '2' => 'Chademo (DC Fast Charging)',
                                '3' => 'DC GBT (Chinese DC Standard)',
                                '4' => 'Type 2 (AC Mennekes)',
                                '5' => 'AC GBT (Chinese AC Standard)',
                            ])
                            ->label('Tipe Port Pengisian Daya (Charger)'),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->visibility('public'),

                TextColumn::make('modelVehicle.brandVehicle.name')
                    ->sortable()
                    ->searchable()
                    ->label('Brand')
                    ->weight('bold'),

                TextColumn::make('modelVehicle.name')
                    ->sortable()
                    ->searchable()
                    ->label('Model')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Varian / Type')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('powertrain')
                    ->label('PT')
                    ->badge()
                    ->colors([
                        'success' => 'BEV',
                        'info' => 'PHEV',
                        'primary' => 'HEV',
                    ])
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('battery_capacity')
                    ->label('Kapasitas Baterai')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' kWh')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1).' kWh' : '—')
                    ->sortable(),

                TextColumn::make('type_charger')
                    ->label('Tipe Charger')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state) {
                        $options = [
                            '1' => 'CCS2',
                            '2' => 'Chademo',
                            '3' => 'DC GBT',
                            '4' => 'Type 2',
                            '5' => 'AC GBT',
                        ];
                        if (is_array($state)) {
                            return implode(', ', array_map(fn ($item) => $options[$item] ?? $item, $state));
                        }
                        $values = array_filter(array_map('trim', explode(',', (string) $state)));
                        return implode(', ', array_map(fn ($item) => $options[$item] ?? $item, $values));
                    }),
            ])
            ->filters([
                SelectFilter::make('brand_vehicle')
                    ->relationship('modelVehicle.brandVehicle', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Brand'),

                SelectFilter::make('model_vehicle')
                    ->relationship('modelVehicle', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Model'),

                TernaryFilter::make('has_battery')
                    ->label('Kapasitas Baterai')
                    ->placeholder('Semua')
                    ->trueLabel('Memiliki Data Baterai (> 0 kWh)')
                    ->falseLabel('Baterai Kosong / Belum Diisi')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('battery_capacity'),
                        false: fn ($query) => $query->whereNull('battery_capacity'),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Actions\EditAction::make(),
                    Actions\ViewAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('model_vehicle_id', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    /** Katalog hanya lahir dari CONNECTING — create manual ditutup. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTypeVehicles::route('/'),
            'create' => Pages\CreateTypeVehicle::route('/create'),
            'view' => Pages\ViewTypeVehicle::route('/{record}'),
            'edit' => Pages\EditTypeVehicle::route('/{record}/edit'),
        ];
    }
}
