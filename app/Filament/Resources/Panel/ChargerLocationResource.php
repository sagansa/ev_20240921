<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\ImageFileUpload;
use App\Filament\Forms\NominalTextInput;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ChargerLocation;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\CheckboxColumn;
use App\Filament\Resources\Panel\ChargerLocationResource\Pages;
use App\Models\CurrentCharger;
use App\Models\PowerCharger;
use App\Models\TypeCharger;
use App\Tables\Columns\LocationOnColumn;
use App\Tables\Columns\StatusLocationColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ToggleColumn;
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class ChargerLocationResource extends Resource
{
    protected static ?string $model = ChargerLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Apps';

    public static function getModelLabel(): string
    {
        return __('crud.chargerLocations.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.chargerLocations.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.chargerLocations.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make()
                    ->schema(static::getAddressFormHeadSchema())
                    ->columns(2),
            ])->columnSpan(['lg' => 1]),

            Group::make()->schema([
                Section::make()
                    ->schema(static::getDataFormHeadSchema()),

                Section::make()
                    ->schema(static::getDetailsFormBottomSchema()),
            ])->columnSpan(['lg' => 1]),

            Section::make()->schema([
                self::getItemsRepeater(),
            ]),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        $chargerLocations = ChargerLocation::query();

        if (Auth::user()->hasRole('user')) {
            $chargerLocations->where('user_id', Auth::id());
        }

        return $table
            ->query($chargerLocations)
            ->poll('60s')
            ->columns([

                ImageColumn::make('image')
                    ->visibility('public')
                    ->openUrlInNewTab()
                    ->url(fn($record) => asset('storage/' . $record->image)),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('provider.name')
                    ->sortable(),

                TextColumn::make('Coordinate')
                    ->getStateUsing(function ($record) {
                        return $record->latitude . ',' . $record->longitude;
                    })
                    ->url(function ($record) {
                        return 'https://www.google.com/maps/place/' . $record->latitude . ',' . $record->longitude;
                    })
                    ->openUrlInNewTab()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('latitude', $direction)
                            ->orderBy('longitude', $direction);
                    }),

                CheckboxColumn::make('parking'),

                TextColumn::make('province.name')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('city.name')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_rest_area'),

                StatusLocationColumn::make('status'),

                LocationOnColumn::make('location_on'),

                TextColumn::make('charges_count')
                    ->label('Total Charge')
                    ->counts('charges')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->visible(fn($record) => auth()->user()->hasRole('super_admin')), // Kondisi visibilitas
            ])

            ->filters([
                SelectFilter::make('provider')
                    ->relationship('provider', 'name'),
                SelectFilter::make('province')
                    ->searchable()
                    ->relationship('province', 'name'),
                SelectFilter::make('city')
                    ->searchable()
                    ->relationship('city', 'name'),
                SelectFilter::make('location_on')
                    ->options([
                        '1' => 'public',
                        '2' => 'private',
                        '3' => 'dealer',
                        '4' => 'closed',
                    ]),
            ], layout: FiltersLayout::AboveContent)

            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn($record) => ($record->status === 1 && $record->user_id === Auth::id()) || Auth::user()->hasRole('super_admin')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListChargerLocations::route('/'),
            'create' => Pages\CreateChargerLocation::route('/create'),
            'view' => Pages\ViewChargerLocation::route('/{record}'),
            'edit' => Pages\EditChargerLocation::route('/{record}/edit'),
        ];
    }

    public static function getAddressFormHeadSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                OSMMap::make('location')
                    ->label('Location')
                    ->showMarker()
                    ->draggable()
                    ->extraControl([
                        'zoomDelta'           => 1,
                        'zoomSnap'            => 0.25,
                        'wheelPxPerZoomLevel' => 60
                    ])
                    ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, $record) {
                        if ($record) {
                            $latitude = $record->latitude;
                            $longitude = $record->longitude;

                            if ($latitude && $longitude) {
                                $set('location', ['lat' => $latitude, 'lng' => $longitude]);
                            }
                        }
                    })
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('latitude', $state['lat']);
                        $set('longitude', $state['lng']);
                    })
                    // tiles url (refer to https://www.spatialbias.com/2018/02/qgis-3.0-xyz-tile-layers/)
                    ->tilesUrl(
                        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                    ),

                Group::make()
                    ->schema([
                        TextInput::make('latitude')
                            ->required()
                            ->hiddenLabel()
                            // ->readOnly(fn() => Auth::user()->hasRole('user'))
                            ->numeric(),
                        TextInput::make('longitude')
                            ->required()
                            ->hiddenLabel()
                            // ->readOnly(fn() => Auth::user()->hasRole('user'))
                            ->numeric(),
                    ])->columns(2),

                // TextInput::make('google_maps_url')
                //     ->required(fn() => !auth()->user()->hasRole('super_admin'))
                //     ->inlineLabel()
                //     ->string(),

            ]),

            Grid::make(['default' => 1])->schema([

                TextInput::make('address')
                    ->nullable()
                    ->inlineLabel()
                    ->string(),

                BaseSelect::make('province_id')
                    ->relationship('province', 'name')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('city_id', null);
                        $set('district_id', null);
                        $set('subdistrict_id', null);
                        $set('postal_code_id', null);
                    }),

                BaseSelect::make('city_id')
                    ->relationship('city', 'name')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->options(function (callable $get) {
                        $provinceId = $get('province_id');
                        return \App\Models\City::where('province_id', $provinceId)->pluck('name', 'id');
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('district_id', null);
                        $set('subdistrict_id', null);
                        $set('postal_code_id', null);
                    }),

                BaseSelect::make('district_id')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->reactive()
                    ->options(function (callable $get) {
                        $cityId = $get('city_id');
                        return \App\Models\District::where('city_id', $cityId)->pluck('name', 'id');
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('subdistrict_id', null);
                        $set('postal_code_id', null);
                    }),

                BaseSelect::make('subdistrict_id')
                    ->relationship('subdistrict', 'name')
                    ->searchable()
                    ->reactive()
                    ->options(function (callable $get) {
                        $districtId = $get('district_id');
                        return \App\Models\Subdistrict::where('district_id', $districtId)->pluck('name', 'id');
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('postal_code_id', null);
                    }),

                BaseSelect::make('postal_code_id')
                    ->relationship('postalCode', 'name')
                    // ->required()
                    ->searchable()
                    ->reactive()
                    ->options(function (callable $get) {
                        $provinceId = $get('province_id');
                        $cityId = $get('city_id');
                        $districtId = $get('district_id');
                        $subdistrictId = $get('subdistrict_id');
                        return \App\Models\PostalCode::where('province_id', $provinceId)
                            ->where('city_id', $cityId)
                            ->where('district_id', $districtId)
                            ->where('subdistrict_id', $subdistrictId)
                            ->pluck('postal_code', 'id');
                    }),

            ]),
        ];
    }

    public static function getDataFormHeadSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                ImageFileUpload::make('image')
                    ->directory('images/charge_location'),

                TextInput::make('name')
                    ->required()
                    ->inlineLabel()
                    ->string(),

                BaseSelect::make('provider_id')
                    ->required()
                    ->relationship('provider', 'name', function ($query) {
                        return $query->where('status', 1);
                    })
                    ->searchable(),

                Group::make()
                    ->schema([
                        Checkbox::make('parking')
                            ->rules(['boolean'])
                            ->inlineLabel(),

                        Checkbox::make('is_rest_area')
                            ->rules(['boolean'])
                            ->inlineLabel(),
                    ])->columns(2),

                BaseSelect::make('status')
                    ->visible(fn() => Auth::user()->hasRole('super_admin'))
                    ->options([
                        '1' => 'not verified',
                        '2' => 'verified',
                        '3' => 'closed',
                        '4' => 'external',
                    ]),

                BaseSelect::make('location_on')
                    ->required()
                    ->default('1')
                    ->options([
                        '1' => 'public',
                        '2' => 'private',
                        '3' => 'dealer',
                        '4' => 'closed',
                    ]),


            ])
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('chargers')
            ->relationship()
            ->minItems(1)
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                $data['user_id'] = auth()->id();

                return $data;
            })
            ->schema([
                Select::make('current_charger_id')
                    ->placeholder('Current ')
                    ->hiddenLabel()
                    ->options(CurrentCharger::query()->pluck('name', 'id'))
                    ->reactive()
                    ->columnSpan([
                        'md' => 4,
                    ])
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('type_charger_id', null);
                        $set('power_charger_id', null);
                        $set('unit', null); // Reset the unit field to its default value or any other desired initial state
                    }),

                Select::make('type_charger_id')
                    ->placeholder('Type')
                    ->hiddenLabel()
                    ->options(function (callable $get) {
                        $currentChargerId = $get('current_charger_id');
                        return TypeCharger::where('current_charger_id', $currentChargerId)->pluck('name', 'id')->toArray();
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('power_charger_id', null);
                        $set('unit', 1); // Reset the unit field to its default value or any other desired initial state
                    })
                    ->columnSpan([
                        'md' => 4,
                    ]),

                Select::make('power_charger_id')
                    ->placeholder('Power')
                    ->hiddenLabel()
                    ->options(function (callable $get) {
                        $typeChargerId = $get('type_charger_id');
                        return PowerCharger::where('type_charger_id', $typeChargerId)->pluck('name', 'id')->toArray();
                    })
                    ->reactive()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 4,
                    ]),

                NominalTextInput::make('unit')
                    ->placeholder('Unit')
                    ->hiddenLabel()
                    ->minValue(1)
                    ->default(1)
                    ->suffix('unit')
                    ->integer()
                    ->columnSpan([
                        'md' => 3,
                    ]),

                Select::make('merk_charger_id')
                    ->placeholder('Merk')
                    ->hiddenLabel()
                    ->searchable()
                    ->relationship('merkCharger', 'name')
                    ->columnSpan([
                        'md' => 4,
                    ]),
            ])
            ->columns([
                'md' => 19,
            ])
            ->defaultItems(1);
    }

    public static function getDetailsFormBottomSchema(): array
    {
        return [
            RichEditor::make('description')
                ->nullable()
                ->string()
                ->fileAttachmentsVisibility('public'),
        ];
    }
}
