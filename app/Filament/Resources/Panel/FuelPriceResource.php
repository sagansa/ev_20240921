<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Columns\CurrencyTextColumn;
use App\Filament\Forms\CurrencyTextInput;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\FuelPrice;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\Panel\FuelPriceResource\Pages;
use Filament\Actions\ActionGroup;

class FuelPriceResource extends Resource
{
    protected static ?string $model = FuelPrice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Aplikasi';
    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Aplikasi';
    }

    public static function getModelLabel(): string
    {
        return __('Harga BBM');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Harga BBM');
    }

    public static function getNavigationLabel(): string
    {
        return __('Harga BBM');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    DatePicker::make('effective_date')
                        ->label('Tanggal Berlaku')
                        ->required(),

                    TextInput::make('fuel_name')
                        ->label('Jenis BBM')
                        ->default('Pertamax')
                        ->required(),

                    CurrencyTextInput::make('price_per_liter')
                        ->label('Harga per Liter')
                        ->prefix('Rp')
                        ->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('effective_date')
                    ->label('Tanggal Berlaku')
                    ->sortable(),

                TextColumn::make('fuel_name')
                    ->label('Jenis BBM')
                    ->sortable(),

                CurrencyTextColumn::make('price_per_liter')
                    ->label('Harga per Liter')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    Actions\EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\CreateAction::make(),
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('effective_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFuelPrices::route('/'),
            'create' => Pages\CreateFuelPrice::route('/create'),
            'edit' => Pages\EditFuelPrice::route('/{record}/edit'),
        ];
    }
}
