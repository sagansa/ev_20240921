<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Columns\CurrencyTextColumn;
use App\Filament\Forms\CurrencyTextInput;
use App\Filament\Forms\TodayDatePicker;
use App\Filament\Resources\Panel\FuelPriceResource\Pages;
use App\Models\FuelPrice;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            Section::make('Informasi Harga BBM')
                ->description('Masukkan rincian harga BBM untuk kalkulasi penghematan biaya.')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('fuel_name')
                            ->label('Jenis BBM')
                            ->default('Pertamax')
                            ->placeholder('Contoh: Pertamax')
                            ->helperText('Nama atau jenis bahan bakar minyak')
                            ->required(),

                        TodayDatePicker::make('effective_date')
                            ->label('Tanggal Berlaku')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText('Tanggal mulai berlakunya harga ini')
                            ->required(),

                        CurrencyTextInput::make('price_per_liter')
                            ->label('Harga per Liter')
                            ->prefix('Rp')
                            ->placeholder('12.500')
                            ->helperText('Harga per liter dalam Rupiah')
                            ->required()
                            ->columnSpanFull(),
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
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->sortable(),

                TextColumn::make('fuel_name')
                    ->label('Jenis BBM')
                    ->searchable()
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

