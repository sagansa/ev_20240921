<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\DecimalTextInput;
use App\Filament\Forms\ImageFileUpload;
use App\Filament\Forms\NominalTextInput;
use App\Filament\Forms\TodayDatePicker;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\StateOfHealth;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\StateOfHealthResource\Pages;
use App\Filament\Resources\Panel\StateOfHealthResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Auth;

class StateOfHealthResource extends Resource
{
    protected static ?string $model = StateOfHealth::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-battery-50';
    protected static string | \UnitEnum | null $navigationGroup = 'Apps';




    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-battery-50';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Apps';
    }

    public static function getModelLabel(): string
    {
        return __('crud.stateOfHealths.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.stateOfHealths.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.stateOfHealths.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    ImageFileUpload::make('image')
                        ->directory('images/soh'),

                    BaseSelect::make('vehicle_id')
                        ->label('Vehicle')
                        ->required()
                        ->options(function () {
                            return Vehicle::where('user_id', Auth::id())
                                ->where('status', 1)
                                ->pluck('license_plate', 'id');
                        })
                        ->searchable(),

                    TodayDatePicker::make('date'),

                    NominalTextInput::make('km')
                        ->label('km')
                        ->suffix('km'),

                    DecimalTextInput::make('percentage')
                        ->suffix('%')
                        ->inputMode('decimal'),

                    TextInput::make('remaining_battery')
                        ->nullable()
                        ->inlineLabel()
                        ->numeric()
                        ->suffix('kWh')
                        ->inputMode('decimal'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');

                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->poll('60s')
            ->columns([
                // ImageColumn::make('image')->visibility('public'),

                TextColumn::make('date'),

                TextColumn::make('vehicle.license_plate'),

                TextColumn::make('km'),

                TextColumn::make('percentage'),

                TextColumn::make('remaining_battery'),

                TextColumn::make('user.name')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin')), // Kondisi visibilitas,
            ])
            ->filters([])
            ->recordActions([
                Actions\EditAction::make(),
                // Actions\ViewAction::make(),
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
            'index' => Pages\ListStateOfHealths::route('/'),
            'create' => Pages\CreateStateOfHealth::route('/create'),
            'view' => Pages\ViewStateOfHealth::route('/{record}'),
            'edit' => Pages\EditStateOfHealth::route('/{record}/edit'),
        ];
    }
}
