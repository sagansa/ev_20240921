<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\TypeCharger;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\TypeChargerResource\Pages;
use App\Filament\Resources\Panel\TypeChargerResource\RelationManagers;

class TypeChargerResource extends Resource
{
    protected static ?string $model = TypeCharger::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Master Charger';
    protected static ?int $navigationSort = 1;




    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-queue-list';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Master Charger';
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Radio::make('name')
                        ->required()
                        ->options([
                            'CCS2' => 'CCS2',
                            'Chademo' => 'Chademo',
                            'DC GBT' => 'DC GBT',
                            'Type 2' => 'Type 2',
                            'AC GBT' => 'AC GBT',
                        ])
                        ->inlineLabel(),


                    Select::make('current_charger_id')
                        ->required()
                        ->relationship('currentCharger', 'name')
                        ,
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            'CCS2' => 'CCS2',
                            'Chademo' => 'Chademo',
                            'DC GBT' => 'DC GBT',
                            'Type 2' => 'Type 2',
                            'AC GBT' => 'AC GBT',
                            default => $state,
                        }
                    ),

                TextColumn::make('currentCharger.name'),
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
            'index' => Pages\ListTypeChargers::route('/'),
            'create' => Pages\CreateTypeCharger::route('/create'),
            'view' => Pages\ViewTypeCharger::route('/{record}'),
            'edit' => Pages\EditTypeCharger::route('/{record}/edit'),
        ];
    }
}
