<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\DecimalTextInput;
use App\Filament\Forms\NominalTextInput;
use App\Filament\Resources\Panel\BatteryResource\Pages;
use App\Models\Battery;
use App\Models\Vehicle;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BatteryResource extends Resource
{
    protected static ?string $model = Battery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-battery-50';

    protected static string|\UnitEnum|null $navigationGroup = 'Aplikasi';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('crud.batteries.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.batteries.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.batteries.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    BaseSelect::make('vehicle_id')
                        ->label('Vehicle')
                        ->required()
                        ->options(function () {
                            return Vehicle::where('user_id', Auth::id())
                                ->where('status', 1)
                                ->pluck('license_plate', 'id');
                        })
                        ->searchable(),

                    TextInput::make('label')
                        ->label('Label')
                        ->nullable()
                        ->placeholder('Battery A / Original'),

                    TextInput::make('serial_number')
                        ->nullable()
                        ->label('Serial Number'),

                    DecimalTextInput::make('capacity_kwh')
                        ->suffix('kWh')
                        ->default(null),

                    DatePicker::make('installed_at')
                        ->required()
                        ->default(today()),

                    NominalTextInput::make('installed_km')
                        ->suffix('km')
                        ->default(0),

                    DatePicker::make('removed_at')
                        ->nullable(),

                    NominalTextInput::make('removed_km')
                        ->suffix('km')
                        ->default(0)
                        ->required(false),

                    Toggle::make('status')
                        ->label('Active')
                        ->inline()
                        ->default(true),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');

                if (! $is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                TextColumn::make('vehicle.license_plate')
                    ->label('Vehicle'),

                TextColumn::make('label')
                    ->label('Label'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Aktif' : 'Pensiun')
                    ->color(fn ($state) => (int) $state === 1 ? 'success' : 'gray'),

                TextColumn::make('capacity_kwh')
                    ->label('Kapasitas')
                    ->suffix(' kWh'),

                TextColumn::make('installed_at')
                    ->date()
                    ->label('Terpasang'),

                TextColumn::make('installed_km')
                    ->label('km Pasang'),

                TextColumn::make('removed_at')
                    ->date()
                    ->label('Pensiun'),

                TextColumn::make('cycle_count')
                    ->label('Siklus')
                    ->badge(),

                TextColumn::make('user.name')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin')), // Kondisi visibilitas,
            ])
            ->filters([])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatteries::route('/'),
            'create' => Pages\CreateBattery::route('/create'),
            'view' => Pages\ViewBattery::route('/{record}'),
            'edit' => Pages\EditBattery::route('/{record}/edit'),
        ];
    }
}
