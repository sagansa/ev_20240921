<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\PowerCharger;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-swatch';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Charger';
    protected static ?int $navigationSort = 3;




    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-swatch';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Master Charger';
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    // Select::make('type_charger_id')
                    //     ->required()
                    //     ->relationship('typeCharger', 'name')
                    //     ->searchable(),

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
            'index' => Pages\ListPowerChargers::route('/'),
            'create' => Pages\CreatePowerCharger::route('/create'),
            'view' => Pages\ViewPowerCharger::route('/{record}'),
            'edit' => Pages\EditPowerCharger::route('/{record}/edit'),
        ];
    }
}
