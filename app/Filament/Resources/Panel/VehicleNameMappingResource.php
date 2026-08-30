<?php

namespace App\Filament\Resources\Panel;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleNameMapping;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CRUD mapping eksplisit nama mentah laporan → katalog. Lapisan PERTAMA
 * pencocokan VehicleSalesMatcher — mengatasi varian nama yang tak tertangkap
 * alias/fuzzy (mis. "WULING-DBG" → Wuling).
 */
class VehicleNameMappingResource extends Resource
{
    protected static ?string $model = VehicleNameMapping::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Mapping Nama Laporan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mapping Nama Laporan';
    }

    public static function getModelLabel(): string
    {
        return 'Mapping Nama';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Nama Mentah di Laporan')
                ->description('Tulis persis seperti yang muncul di CSV GAIKINDO (MODEL / TYPE MODEL).')
                ->schema([
                    TextInput::make('raw_brand')->label('Raw Brand')->required()->maxLength(255),
                    TextInput::make('raw_model')->label('Raw Model / Type Model')->required()->maxLength(255),
                    TextInput::make('catatan')->label('Catatan')->maxLength(255)->columnSpanFull(),
                ]),
            Section::make('Dipetakan Ke Katalog')
                ->description('Katalog harus sudah ada — mapping tidak pernah membuat brand/model baru.')
                ->schema([
                    Select::make('brand_vehicle_id')
                        ->label('Brand Vehicle')
                        ->options(fn () => BrandVehicle::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('model_vehicle_id', null)),
                    Select::make('model_vehicle_id')
                        ->label('Model Vehicle')
                        ->options(fn ($get) => ModelVehicle::query()
                            ->when($get('brand_vehicle_id'), fn ($q, $brandId) => $q->where('brand_vehicle_id', $brandId))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('type_vehicle_id')
                        ->label('Type Vehicle (opsional)')
                        ->options(fn ($get) => TypeVehicle::query()
                            ->when($get('model_vehicle_id'), fn ($q, $modelId) => $q->where('model_vehicle_id', $modelId))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('raw_brand')
                    ->label('Raw Brand')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('raw_model')
                    ->label('Raw Model')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('brandVehicle.name')
                    ->label('→ Brand Katalog')
                    ->badge()
                    ->color('success'),
                TextColumn::make('modelVehicle.name')
                    ->label('→ Model Katalog')
                    ->searchable(),
                TextColumn::make('typeVehicle.name')
                    ->label('→ Type')
                    ->placeholder('—'),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                //
            ]);
    }
}
