<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use App\Models\Charge;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Panel\ChargeResource\Pages;
use App\Filament\Resources\Panel\ChargeResource\RelationManagers;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.charges.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.charges.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.charges.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('vehicle_id')
                        ->required()
                        ->relationship('vehicle', 'id')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    DatePicker::make('date')
                        ->rules(['date'])
                        ->required()
                        ->native(false),

                    Select::make('charger_location_id')
                        ->required()
                        ->relationship('chargerLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('charger_id')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('km_now')
                        ->required()
                        ->numeric()
                        ->step()
                        ->suffix('km'),

                    TextInput::make('km_before')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('km'),

                    TextInput::make('start_charging_now')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('%'),

                    TextInput::make('finish_charging_now')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('%'),

                    TextInput::make('finish_charging_before')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('%'),

                    TextInput::make('parking')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->prefix('Rp'),

                    TextInput::make('kWh')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('kWh'),

                    TextInput::make('street_lighting_tax')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->prefix('Rp'),

                    TextInput::make('value_added_tax')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->prefix('Rp'),

                    TextInput::make('admin_cost')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->prefix('Rp'),

                    TextInput::make('total_cost')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->prefix('Rp'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('vehicle.id'),

                TextColumn::make('date')->since(),

                TextColumn::make('chargerLocation.name'),

                TextColumn::make('charger_id'),

                TextColumn::make('km_now')->numeric(thousandsSeparator: '.'),

                TextColumn::make('km_before'),

                TextColumn::make('start_charging_now'),

                TextColumn::make('finish_charging_now'),

                TextColumn::make('finish_charging_before'),

                TextColumn::make('parking')->numeric(thousandsSeparator: '.'),

                TextColumn::make('kWh')->numeric(
                    decimalPlaces: 2,
                    decimalSeparator: ',',
                    thousandsSeparator: '.'
                ),

                TextColumn::make('street_lighting_tax')->numeric(
                    thousandsSeparator: '.'
                ),

                TextColumn::make('value_added_tax')->numeric(
                    thousandsSeparator: '.'
                ),

                TextColumn::make('admin_cost')->numeric(
                    thousandsSeparator: '.'
                ),

                TextColumn::make('total_cost')->numeric(
                    thousandsSeparator: '.'
                ),

                TextColumn::make('user.name'),
            ])
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListCharges::route('/'),
            'create' => Pages\CreateCharge::route('/create'),
            'view' => Pages\ViewCharge::route('/{record}'),
            'edit' => Pages\EditCharge::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
