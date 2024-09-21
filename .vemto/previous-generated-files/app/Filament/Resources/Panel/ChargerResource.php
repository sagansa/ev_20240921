<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use App\Models\Charger;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\ChargerResource\Pages;
use App\Filament\Resources\Panel\ChargerResource\RelationManagers;

class ChargerResource extends Resource
{
    protected static ?string $model = Charger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.chargers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.chargers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.chargers.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('charger_location_id')
                        ->required()
                        ->relationship('chargerLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('current_charger_id')
                        ->required()
                        ->relationship('currentCharger', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('type_charger_id')
                        ->required()
                        ->relationship('typeCharger', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('power_charger_id')
                        ->required()
                        ->relationship('powerCharger', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('unit')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('charger_cost')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('electric_lighting_tax')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('admin_cost')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('value_added_tax')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('status')
                        ->required()
                        ->numeric()
                        ->step(1),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('chargerLocation.name'),

                TextColumn::make('currentCharger.name'),

                TextColumn::make('typeCharger.name'),

                TextColumn::make('powerCharger.name'),

                TextColumn::make('unit'),

                TextColumn::make('charger_cost'),

                TextColumn::make('electric_lighting_tax'),

                TextColumn::make('admin_cost'),

                TextColumn::make('value_added_tax'),

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
            'index' => Pages\ListChargers::route('/'),
            'create' => Pages\CreateCharger::route('/create'),
            'view' => Pages\ViewCharger::route('/{record}'),
            'edit' => Pages\EditCharger::route('/{record}/edit'),
        ];
    }
}
