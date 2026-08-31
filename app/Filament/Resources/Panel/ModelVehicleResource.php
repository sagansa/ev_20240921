<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use App\Filament\Resources\Panel\ModelVehicleResource\Pages;
use App\Filament\Resources\Panel\ModelVehicleResource\RelationManagers;
use App\Models\ModelVehicle;
use App\Support\VehicleCategories;
use Filament\Actions;
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

class ModelVehicleResource extends Resource
{
    protected static ?string $model = ModelVehicle::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Referensi Kendaraan';
    }

    public static function getModelLabel(): string
    {
        return __('crud.modelVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.modelVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.modelVehicles.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identitas Model Kendaraan')
                ->description('Informasi dasar merek dan model kendaraan.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Select::make('brand_vehicle_id')
                            ->required()
                            ->relationship('brandVehicle', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Brand / Merek')
                            ->autofocus(),

                        TextInput::make('name')
                            ->required()
                            ->string()
                            ->label('Nama Model')
                            ->placeholder('cth. Air EV, Seal, Ioniq 5, Binguo EV, Omoda E5'),

                        ImageFileUpload::make('image')
                            ->directory('images/model')
                            ->image()
                            ->label('Foto / Ilustrasi Model')
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Klasifikasi & Taksonomi Kendaraan')
                ->description('Karakteristik teknis yang digunakan untuk katalog, segmentasi pasar EV, dan integrasi GAIKINDO.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Select::make('powertrain')
                            ->options([
                                'BEV' => '⚡ BEV (Battery Electric Vehicle)',
                                'PHEV' => '🔌 PHEV (Plug-in Hybrid)',
                                'HEV' => '🔋 HEV (Hybrid Electric)',
                                'ICE' => '⛽ ICE (Internal Combustion Engine)',
                            ])
                            ->default('BEV')
                            ->required()
                            ->label('Powertrain / Sistem Penggerak'),

                        Select::make('category')
                            ->options(array_combine(VehicleCategories::CATEGORIES, VehicleCategories::CATEGORIES))
                            ->searchable()
                            ->label('Kategori Kendaraan')
                            ->placeholder('Pilih Kategori (cth. SUV, MPV, City Car)'),

                        Select::make('size_class')
                            ->options(array_combine(VehicleCategories::SIZES, VehicleCategories::SIZES))
                            ->label('Ukuran (Size Class)')
                            ->placeholder('Pilih Ukuran (Compact, Medium, Large)'),
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

                TextColumn::make('brandVehicle.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Model')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('vehicles_count')
                    ->counts('vehicles')
                    ->label('Dipakai User')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('powertrain')
                    ->label('Powertrain')
                    ->badge()
                    ->colors([
                        'success' => 'BEV',
                        'info' => 'PHEV',
                        'primary' => 'HEV',
                        'gray' => 'ICE',
                    ])
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ?? '⚠️ Tanpa Kategori')
                    ->sortable(),

                TextColumn::make('size_class')
                    ->label('Ukuran')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('type_vehicles_count')
                    ->counts('typeVehicles')
                    ->label('Varian Type')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('brand_vehicle_id')
                    ->relationship('brandVehicle', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Brand'),

                SelectFilter::make('powertrain')
                    ->options([
                        'BEV' => '⚡ BEV (Murni)',
                        'PHEV' => '🔌 PHEV (Plug-in)',
                        'HEV' => '🔋 HEV (Hybrid)',
                        'ICE' => '⛽ ICE (Bensin/Diesel)',
                    ])
                    ->label('Powertrain'),

                SelectFilter::make('category')
                    ->options(array_combine(VehicleCategories::CATEGORIES, VehicleCategories::CATEGORIES))
                    ->searchable()
                    ->label('Kategori'),

                TernaryFilter::make('has_category')
                    ->label('Status Kategori')
                    ->placeholder('Semua Status')
                    ->trueLabel('Kategori Lengkap')
                    ->falseLabel('⚠️ Tanpa Kategori (Perlu Diisi)')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('category'),
                        false: fn ($query) => $query->whereNull('category'),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Actions\EditAction::make(),
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('brand_vehicle_id', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TypeVehiclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModelVehicles::route('/'),
            'create' => Pages\CreateModelVehicle::route('/create'),
            'view' => Pages\ViewModelVehicle::route('/{record}'),
            'edit' => Pages\EditModelVehicle::route('/{record}/edit'),
        ];
    }
}
