<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\RelationManagers;

use App\Filament\Forms\ImageFileUpload;
use App\Support\VehicleCategories;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModelVehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'modelVehicles';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(['default' => 1, 'md' => 2])->schema([
                ImageFileUpload::make('image')
                    ->directory('images/model')
                    ->label('Foto Model'),

                TextInput::make('name')
                    ->required()
                    ->string()
                    ->label('Nama Model')
                    ->placeholder('cth. Air EV, Seal, Ioniq 5'),

                Select::make('powertrain')
                    ->options([
                        'BEV' => '⚡ BEV (Battery EV)',
                        'PHEV' => '🔌 PHEV (Plug-in Hybrid)',
                        'HEV' => '🔋 HEV (Hybrid)',
                        'ICE' => '⛽ ICE (Bensin/Diesel)',
                    ])
                    ->default('BEV')
                    ->required()
                    ->label('Powertrain'),

                Select::make('category')
                    ->options(array_combine(VehicleCategories::CATEGORIES, VehicleCategories::CATEGORIES))
                    ->searchable()
                    ->label('Kategori'),

                Select::make('size_class')
                    ->options(array_combine(VehicleCategories::SIZES, VehicleCategories::SIZES))
                    ->label('Ukuran (Size Class)'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->visibility('public'),

                TextColumn::make('name')
                    ->label('Nama Model')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('powertrain')
                    ->badge()
                    ->colors([
                        'success' => 'BEV',
                        'info' => 'PHEV',
                        'primary' => 'HEV',
                        'gray' => 'ICE',
                    ]),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ?? '⚠️ Tanpa Kategori'),

                TextColumn::make('size_class')
                    ->label('Ukuran')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('type_vehicles_count')
                    ->counts('typeVehicles')
                    ->label('Jumlah Type')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([])
            ->headerActions([Actions\CreateAction::make()])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
