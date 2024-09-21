<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TypeVehicle;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\CheckboxColumn;
use App\Filament\Resources\Panel\TypeVehicleResource\Pages;
use App\Filament\Resources\Panel\TypeVehicleResource\RelationManagers;

class TypeVehicleResource extends Resource
{
    protected static ?string $model = TypeVehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Vehicles';

    public static function getModelLabel(): string
    {
        return __('crud.typeVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->autofocus(),

                    Select::make('model_vehicle_id')
                        ->required()
                        ->relationship('modelVehicle', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('battery_capacity')
                        ->required()
                        ->numeric()
                        ->step()
                        ->suffix('kWh')
                        ->inputMode('decimal'),

                    Checkbox::make('type_charger')
                        ->required()
                        ->inline(),

                    Select::make('type_charger')
                        ->required()
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options([
                            '1' => 'CCS2',
                            '2' => 'Chademo',
                            '3' => 'DC GBT',
                            '4' => 'Type 2',
                            '5' => 'AC GBT',
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
                TextColumn::make('name'),

                TextColumn::make('modelVehicle.name'),

                TextColumn::make('battery_capacity'),

                CheckboxColumn::make('type_charger'),

                TextColumn::make('type_charger'),
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
            'index' => Pages\ListTypeVehicles::route('/'),
            'create' => Pages\CreateTypeVehicle::route('/create'),
            'view' => Pages\ViewTypeVehicle::route('/{record}'),
            'edit' => Pages\EditTypeVehicle::route('/{record}/edit'),
        ];
    }
}
