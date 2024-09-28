<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Columns\CurrencyTextColumn;
use App\Filament\Columns\DecimalTextColumn;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\CurrencyTextInput;
use App\Filament\Forms\DecimalTextInput;
use App\Filament\Forms\TodayDatePicker;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use App\Models\DiscountHomeCharging;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\Panel\DiscountHomeChargingResource\Pages;
use App\Filament\Resources\Panel\DiscountHomeChargingResource\RelationManagers;
use App\Models\ChargerLocation;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Type\Decimal;

class DiscountHomeChargingResource extends Resource
{
    protected static ?string $model = DiscountHomeCharging::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.discountHomeChargings.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.discountHomeChargings.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.discountHomeChargings.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    BaseSelect::make('charger_location_id')
                        ->label('Charger Location')
                        ->searchable()
                        ->options(function () {
                            return ChargerLocation::where('user_id', Auth::id())
                                ->where('provider_id', 'd89fa3a2-00c6-4d13-b4db-5066b33ebd17')
                                ->pluck('name', 'id');
                        }),

                    TodayDatePicker::make('month'),

                    DecimalTextInput::make('total_kwh')
                        ->suffix('kWh'),

                    DecimalTextInput::make('discount_kwh')
                        ->suffix('kWh'),

                    CurrencyTextInput::make('discount_total')
                        ->prefix('Rp'),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $query = DiscountHomeCharging::query();

        if (!Auth::user()->hasRole('super-admin')) {
            $query->where('user_id', Auth::id());
        }

        return $table
            ->query($query)
            ->poll('60s')
            ->columns([
                TextColumn::make('chargerLocation.name'),

                TextColumn::make('month')->since(),

                DecimalTextColumn::make('total_kwh'),

                DecimalTextColumn::make('discount_kwh'),

                CurrencyTextColumn::make('discount_total'),

                TextColumn::make('user.name')->hidden(fn () => Auth::user()->hasRole('user'))
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
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
            'index' => Pages\ListDiscountHomeChargings::route('/'),
            'create' => Pages\CreateDiscountHomeCharging::route('/create'),
            'view' => Pages\ViewDiscountHomeCharging::route('/{record}'),
            'edit' => Pages\EditDiscountHomeCharging::route('/{record}/edit'),
        ];
    }
}
