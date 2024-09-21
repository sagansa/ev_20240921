<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StateOfHealth;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
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

    protected static ?string $navigationIcon = 'heroicon-o-battery-50';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Apps';

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
                        ->label('Vehicle')
                        ->required()
                        ->options(function () {
                            return Vehicle::where('user_id', Auth::id())
                                ->where('status', 1)
                                ->pluck('license_plate', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->native(false),

                    DatePicker::make('date')
                        ->rules(['date'])
                        ->default(today())
                        ->required()
                        ->native(false),

                    TextInput::make('km')
                        ->label('km')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('km')
                        ->inputMode('numeric'),

                    TextInput::make('percentage')
                        ->required()
                        ->numeric()
                        ->suffix('%')
                        ->inputMode('decimal'),

                    TextInput::make('remaining_battery')
                        ->nullable()
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
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('date'),

                TextColumn::make('vehicle.license_plate'),

                TextColumn::make('km'),

                TextColumn::make('percentage'),

                TextColumn::make('remaining_battery'),

                TextColumn::make('user.name')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin')), // Kondisi visibilitas,
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListStateOfHealths::route('/'),
            'create' => Pages\CreateStateOfHealth::route('/create'),
            'view' => Pages\ViewStateOfHealth::route('/{record}'),
            'edit' => Pages\EditStateOfHealth::route('/{record}/edit'),
        ];
    }
}
