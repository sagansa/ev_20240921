<?php

namespace App\Filament\Resources\Panel;

use Filament\Support\Components\Badge;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TypeVehicle;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\TypeVehicleResource\Pages;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Filters\SelectFilter;
use Termwind\Components\Span;

class TypeVehicleResource extends Resource
{
    protected static ?string $model = TypeVehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-asia-australia';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Databases';

    public static function getModelLabel(): string
    {
        return __('crud.typeVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.typeVehicles.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('model_vehicle_id')
                        ->required()
                        ->relationship('modelVehicle', 'name')
                        ->searchable(),

                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->autofocus(),

                    TextInput::make('battery_capacity')
                        ->nullable()
                        ->numeric()
                        ->suffix('kWh')
                        ->inputMode('decimal'),

                    Select::make('type_charger')
                        ->required()
                        ->multiple()
                        ->searchable()
                        ->options([
                            '1' => 'CCS2',
                            '2' => 'Chademo',
                            '3' => 'DC GBT',
                            '4' => 'Type 2',
                            '5' => 'AC GBT',
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('modelVehicle.brandVehicle.name')
                    ->sortable()
                    ->label('Brand'),

                TextColumn::make('modelVehicle.name')
                    ->sortable()
                    ->label('Model'),

                TextColumn::make('name')
                    ->label('Type'),

                TextColumn::make('battery_capacity')
                    ->sortable()
                    ->suffix(' kWh'),

                TextColumn::make('type_charger')
                    ->label('Type Charger')
                    ->formatStateUsing(function ($state) {
                        $options = [
                            '1' => 'CCS2',
                            '2' => 'Chademo',
                            '3' => 'DC GBT',
                            '4' => 'Type 2',
                            '5' => 'AC GBT',
                        ];
                        $values = array_map('trim', explode(',', $state));
                        return implode(', ', array_map(function ($item) use ($options) {
                            return $options[$item] ?? '';
                        }, $values));
                    }),

            ])
            ->filters([
                SelectFilter::make('brand_vehicle')
                    ->relationship('modelVehicle.brandVehicle','name'),
                SelectFilter::make('model_vehicle')
                    ->relationship('modelVehicle','name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(function ($query) {
                $query->orderByRaw('RAND()');
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTypeVehicles::route('/'),
            'create' => Pages\CreateTypeVehicle::route('/create'),
            'view' => Pages\ViewTypeVehicle::route('/{record}'),
            'edit' => Pages\EditTypeVehicle::route('/{record}/edit'),
        ];
    }

    public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
        ]);
}
}
