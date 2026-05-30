<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\BrandVehicle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\BrandVehicleResource\Pages;
use App\Filament\Resources\Panel\BrandVehicleResource\RelationManagers;

class BrandVehicleResource extends Resource
{
    protected static ?string $model = BrandVehicle::class;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Vehicles';
    }

    public static function getModelLabel(): string
    {
        return __('crud.brandVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.brandVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.brandVehicles.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageFileUpload::make('image')
                        ->directory('images/brand'),

                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->autofocus(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('name'),
            ])
            ->filters([])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ModelVehiclesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrandVehicles::route('/'),
            'create' => Pages\CreateBrandVehicle::route('/create'),
            'view' => Pages\ViewBrandVehicle::route('/{record}'),
            'edit' => Pages\EditBrandVehicle::route('/{record}/edit'),
        ];
    }
}