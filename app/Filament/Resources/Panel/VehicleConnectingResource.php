<?php

namespace App\Filament\Resources\Panel;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Tabel CONNECTING (persistensi master mapping "BRAND MODEL TYPE" → katalog).
 * Sumber utama tetap file CSV; tabel ini menyimpan isi + link ke katalog
 * agar bisa dibandingkan/di-query dari database.
 */
class VehicleConnectingResource extends Resource
{
    protected static ?string $model = VehicleConnecting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-table-cells';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Connecting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Connecting';
    }

    public static function getModelLabel(): string
    {
        return 'Baris Connecting';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Teks Mentah Laporan')
                ->schema([
                    TextInput::make('raw_gabungan')->label('BRAND MODEL TYPE (raw)')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('fuel')->label('FUEL')->maxLength(16),
                    TextInput::make('brand_name')->label('Brand (parsed)')->maxLength(255),
                    TextInput::make('model_name')->label('Model (parsed)')->maxLength(255),
                    TextInput::make('type_name')->label('Type (parsed)')->maxLength(255),
                ]),
            Section::make('Katalog')
                ->schema([
                    Select::make('brand_vehicle_id')
                        ->label('Brand Vehicle')
                        ->options(fn () => BrandVehicle::orderBy('name')->pluck('name', 'id'))
                        ->searchable()->live()
                        ->afterStateUpdated(fn ($set) => $set('model_vehicle_id', null)),
                    Select::make('model_vehicle_id')
                        ->label('Model Vehicle')
                        ->options(fn ($get) => ModelVehicle::query()
                            ->when($get('brand_vehicle_id'), fn ($q, $b) => $q->where('brand_vehicle_id', $b))
                            ->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->live()
                        ->afterStateUpdated(fn ($set) => $set('type_vehicle_id', null)),
                    Select::make('type_vehicle_id')
                        ->label('Type Vehicle')
                        ->options(fn ($get) => TypeVehicle::query()
                            ->when($get('model_vehicle_id'), fn ($q, $m) => $q->where('model_vehicle_id', $m))
                            ->orderBy('name')->pluck('name', 'id'))
                        ->searchable(),
                ]),
            Section::make('Klasifikasi')
                ->schema([
                    TextInput::make('powertrain')->label('Powertrain')->maxLength(8),
                    TextInput::make('category')->label('Kategori')->maxLength(255),
                    TextInput::make('size_class')->label('Ukuran')->maxLength(16),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('raw_gabungan')
                    ->label('BRAND MODEL TYPE')
                    ->fontFamily('mono')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('brandVehicle.name')
                    ->label('→ Brand')
                    ->badge()
                    ->color('success')
                    ->placeholder('—'),
                TextColumn::make('modelVehicle.name')
                    ->label('→ Model')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('typeVehicle.name')
                    ->label('→ Type')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('powertrain')->label('PT')->badge(),
                TextColumn::make('category')->label('Kategori')->badge()->color('info')->placeholder('—'),
                TextColumn::make('size_class')->label('Ukuran')->placeholder('—'),
            ]);
    }
}
