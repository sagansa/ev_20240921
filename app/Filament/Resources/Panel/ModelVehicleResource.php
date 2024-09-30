<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ModelVehicle;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Vehicles';

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

    public static function form(Form $form): Form
    {
        return $form->schema([
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
