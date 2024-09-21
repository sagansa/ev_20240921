<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TypeCharger;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\TypeChargerResource\Pages;
use App\Filament\Resources\Panel\TypeChargerResource\RelationManagers;

class TypeChargerResource extends Resource
{
    protected static ?string $model = TypeCharger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Chargers';

    public static function getModelLabel(): string
    {
        return __('crud.typeChargers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.typeChargers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.typeChargers.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Radio::make('name')
                        ->required()
                        ->options([
                            'AC' => 'AC',
                            'DC' => 'DC',
                        ])
                        ->inlineLabel(),

                    Select::make('current_charger_id')
                        ->required()
                        ->relationship('currentCharger', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
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

                TextColumn::make('currentCharger.name'),
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
            'index' => Pages\ListTypeChargers::route('/'),
            'create' => Pages\CreateTypeCharger::route('/create'),
            'view' => Pages\ViewTypeCharger::route('/{record}'),
            'edit' => Pages\EditTypeCharger::route('/{record}/edit'),
        ];
    }
}
