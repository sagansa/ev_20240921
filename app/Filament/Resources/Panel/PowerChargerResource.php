<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PowerCharger;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\PowerChargerResource\Pages;
use App\Filament\Resources\Panel\PowerChargerResource\RelationManagers;
use App\Models\TypeCharger;
use Filament\Forms\Components\Radio;

class PowerChargerResource extends Resource
{
    protected static ?string $model = PowerCharger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Chargers';

    public static function getModelLabel(): string
    {
        return __('crud.powerChargers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.powerChargers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.powerChargers.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    // Select::make('type_charger_id')
                    //     ->required()
                    //     ->relationship('typeCharger', 'name')
                    //     ->searchable()
                    //     ->preload()
                    //     ->native(false),

                    Radio::make('type_charger_id')
                        ->label('Type')
                        ->required()
                        ->inline()
                        ->options(TypeCharger::pluck('name', 'id')),

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
                TextColumn::make('typeCharger.currentCharger.name')
                    ->label('current'),

                TextColumn::make('typeCharger.name')
                    ->label('type'),

                TextColumn::make('name')
                    ->label('power'),
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
            'index' => Pages\ListPowerChargers::route('/'),
            'create' => Pages\CreatePowerCharger::route('/create'),
            'view' => Pages\ViewPowerCharger::route('/{record}'),
            'edit' => Pages\EditPowerCharger::route('/{record}/edit'),
        ];
    }
}
