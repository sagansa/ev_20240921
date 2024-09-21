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
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
                    FileUpload::make('image')
                        ->rules(['image'])
                        ->nullable()
                        ->maxSize(1024)
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']),

                    Select::make('vehicle_id')
                        ->required()
                        ->relationship('vehicle', 'image')
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
                        ->step(1)
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
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('vehicle.image'),

                TextColumn::make('date')->since(),

                TextColumn::make('chargerLocation.name'),

                TextColumn::make('charger_id'),

                TextColumn::make('km_now'),

                TextColumn::make('km_before'),

                TextColumn::make('start_charging_now'),

                TextColumn::make('finish_charging_now'),

                TextColumn::make('finish_charging_before'),

                TextColumn::make('parking'),

                TextColumn::make('kWh'),

                TextColumn::make('street_lighting_tax'),

                TextColumn::make('value_added_tax'),

                TextColumn::make('admin_cost'),

                TextColumn::make('total_cost'),

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
            'index' => Pages\ListCharges::route('/'),
            'create' => Pages\CreateCharge::route('/create'),
            'view' => Pages\ViewCharge::route('/{record}'),
            'edit' => Pages\EditCharge::route('/{record}/edit'),
        ];
    }
}
