<?php

namespace App\Filament\Resources\Panel;

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

class DiscountHomeChargingResource extends Resource
{
    protected static ?string $model = DiscountHomeCharging::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Grid::make(['default' => 1])->schema([
                    Select::make('charger_location_id')
                        ->required()
                        ->relationship('chargerLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    DatePicker::make('month')
                        ->rules(['date'])
                        ->required()
                        ->native(false),

                    TextInput::make('total_kwh')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('discount_kwh')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('discount_total')
                        ->required()
                        ->numeric()
                        ->step(1),

                    Select::make('user_id')
                        ->required()
                        ->relationship('user', 'name')
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
                TextColumn::make('chargerLocation.name'),

                TextColumn::make('month')->since(),

                TextColumn::make('total_kwh'),

                TextColumn::make('discount_kwh'),

                TextColumn::make('discount_total'),

                TextColumn::make('user.name'),
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
            'index' => Pages\ListDiscountHomeChargings::route('/'),
            'create' => Pages\CreateDiscountHomeCharging::route('/create'),
            'view' => Pages\ViewDiscountHomeCharging::route('/{record}'),
            'edit' => Pages\EditDiscountHomeCharging::route('/{record}/edit'),
        ];
    }
}
