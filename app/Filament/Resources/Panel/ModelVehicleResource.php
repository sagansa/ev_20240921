<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\ModelVehicle;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\ModelVehicleResource\Pages;
use App\Filament\Resources\Panel\ModelVehicleResource\RelationManagers;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;

class ModelVehicleResource extends Resource
{
    protected static ?string $model = ModelVehicle::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Vehicles';




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
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    ImageFileUpload::make('image')
                        ->directory('images/model'),

                    Select::make('brand_vehicle_id')
                        ->required()
                        ->relationship('brandVehicle', 'name')
                        ->searchable(),

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

                TextColumn::make('brandVehicle.name'),
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
        return [];
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
