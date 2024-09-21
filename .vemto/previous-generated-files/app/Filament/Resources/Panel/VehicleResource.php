<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use App\Models\Vehicle;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\Panel\VehicleResource\Pages;
use App\Filament\Resources\Panel\VehicleResource\RelationManagers;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.vehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    TextInput::make('license_plate')
                        ->string()
                        ->required(),

                    Select::make('brand_vehicle_id')
                        ->required()
                        ->relationship('brandVehicle', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('model_vehicle_id')
                        ->required()
                        ->relationship('modelVehicle', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('type_vehicle_id')
                        ->nullable()
                        ->relationship('typeVehicle', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    DatePicker::make('ownership')
                        ->rules(['date'])
                        ->nullable()
                        ->native(false),

                    Select::make('status')
                        ->required()
                        ->default('1')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options([
                            '1' => 'active',
                            '2' => 'inactive',
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('license_plate'),

                TextColumn::make('brandVehicle.name'),

                TextColumn::make('modelVehicle.name'),

                TextColumn::make('typeVehicle.name'),

                TextColumn::make('ownership')->since(),

                TextColumn::make('status'),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
