<?php

namespace App\Filament\Resources\Panel\BrandVehicleResource\RelationManagers;

use App\Filament\Forms\ImageFileUpload;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\BrandVehicleResource;
use Filament\Resources\RelationManagers\RelationManager;

class ModelVehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'modelVehicles';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(['default' => 1])->schema([
                ImageFileUpload::make('image')->directory('images/model'),

                TextInput::make('name')
                    ->required()
                    ->string(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('name'),
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
