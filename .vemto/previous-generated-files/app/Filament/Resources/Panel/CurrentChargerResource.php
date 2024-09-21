<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CurrentCharger;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\CurrentChargerResource\Pages;
use App\Filament\Resources\Panel\CurrentChargerResource\RelationManagers;

class CurrentChargerResource extends Resource
{
    protected static ?string $model = CurrentCharger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Chargers';

    public static function getModelLabel(): string
    {
        return __('crud.currentChargers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.currentChargers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.currentChargers.collectionTitle');
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
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([TextColumn::make('name')])
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
            'index' => Pages\ListCurrentChargers::route('/'),
            'create' => Pages\CreateCurrentCharger::route('/create'),
            'view' => Pages\ViewCurrentCharger::route('/{record}'),
            'edit' => Pages\EditCurrentCharger::route('/{record}/edit'),
        ];
    }
}
