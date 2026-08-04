<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\PlnChargerLocationResource\Pages;
use App\Filament\Resources\Panel\PlnChargerLocationResource\RelationManagers\DetailsRelationManager;
use App\Models\PlnChargerLocation;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PlnChargerLocationResource extends Resource
{
    protected static ?string $model = PlnChargerLocation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'PLN Charger Location';

    protected static ?string $navigationLabel = 'List PLN Charger Location';

    protected static ?string $modelLabel = 'PLN Charger Location';

    protected static ?string $pluralModelLabel = 'PLN Charger Location';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pln_id')
                    ->label('ID PLN')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('provider.name')
                    ->label('Provider EV')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('owner_machine')
                    ->label('Pemilik Mesin')
                    ->sortable(),
                TextColumn::make('province.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('clusterIsland.name')
                    ->label('Cluster Pulau')
                    ->sortable(),
                TextColumn::make('locationCategory.name')
                    ->label('Kategori Lokasi')
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Latitude')
                    ->numeric(),
                TextColumn::make('longitude')
                    ->label('Longitude')
                    ->numeric(),
            ])
            ->filters([
                SelectFilter::make('provider_id')
                    ->label('Filter Provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('province_id')
                    ->label('Filter Provinsi')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlnChargerLocations::route('/'),
            'view' => Pages\ViewPlnChargerLocation::route('/{record}'),
        ];
    }
}
