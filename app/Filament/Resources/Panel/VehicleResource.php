<?php

namespace App\Filament\Resources\Panel;

use Filament\Tables;
use App\Models\Vehicle;
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
use App\Filament\Resources\Panel\VehicleResource\Pages;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Tables\Columns\StatusActiveColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Auth;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.vehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
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

                    TextInput::make('license_plate')
                        ->label('License Plate/Name Your Vehicle')
                        ->string()
                        ->required(),

                    Select::make('brand_vehicle_id')
                        ->label('Brand')
                        ->required()
                        ->relationship('brandVehicle', 'name')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('model_vehicle_id', null);
                            $set('type_vehicle_id', null);
                        }),

                    Select::make('model_vehicle_id')
                        ->label('Model')
                        ->options(function (callable $get) {
                            $brandVehicleId = $get('brand_vehicle_id');
                            return ModelVehicle::where('brand_vehicle_id', $brandVehicleId)->pluck('name', 'id')->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('type_vehicle_id', null);
                        }),

                    Select::make('type_vehicle_id')
                        ->label('Type')
                        ->options(function (callable $get) {
                            $modelVehicleId = $get('model_vehicle_id');
                            return TypeVehicle::where('model_vehicle_id', $modelVehicleId)->pluck('name', 'id')->toArray();
                        })
                        ->nullable()
                        ->searchable()
                        ->reactive(),

                    DatePicker::make('ownership')
                        ->rules(['date'])
                        ->nullable()
                        ->native(false),

                    Select::make('status')
                        ->required()
                        ->default('1')
                        ->searchable()
                        ->options([
                            '1' => 'active',
                            '2' => 'inactive',
                        ]),
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

                TextColumn::make('license_plate')
                    ->label('Name Your Vehicle'),

                TextColumn::make('brandVehicle.name')
                    ->label('brand'),

                TextColumn::make('modelVehicle.name')
                    ->label('Model'),

                TextColumn::make('typeVehicle.name')
                    ->label('Type'),

                TextColumn::make('max_km_now')
                    ->label('km')
                    ->numeric(
                        thousandsSeparator: '.'
                    ),

                TextColumn::make('ownership'),

                StatusActiveColumn::make('status'),

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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
