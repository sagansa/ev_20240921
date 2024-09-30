<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Providers;
use App\Filament\Forms\ImageFileUpload;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Provider;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\ProviderResource\Pages;
use App\Filament\Resources\Panel\ProviderResource\RelationManagers;
use App\Tables\Columns\StatusActiveColumn;
use Illuminate\Support\Facades\Auth;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    // protected static ?string $cluster = Providers::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Databases';

    public static function getModelLabel(): string
    {
        return __('crud.providers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.providers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.providers.collectionTitle');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageFileUpload::make('image')
                        ->directory('images/provider'),

                    TextInput::make('name')
                        ->required()
                        ->string(),

                    TextInput::make('contact')
                        ->nullable()
                        ->string(),

                    TextInput::make('address')
                        ->nullable()
                        ->string(),

                    Select::make('province_id')
                        ->required()
                        ->relationship('province', 'name')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('city_id', null);
                            $set('district_id', null);
                            $set('subdistrict_id', null);
                            $set('postal_code_id', null);
                        }),

                Select::make('city_id')
                    ->required()
                    ->relationship('city', 'name')
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

                Select::make('district_id')
                    ->nullable()
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

                Select::make('subdistrict_id')
                    ->nullable()
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

                Select::make('postal_code_id')
                    ->nullable()
                    ->relationship('postalCode', 'name')
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
                            ->pluck('name', 'id');
                    }),

                Select::make('status')
                    ->required()
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
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('name')
                    ->sortable(),

                TextColumn::make('contact'),

                StatusActiveColumn::make('status')
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),
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
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'view' => Pages\ViewProvider::route('/{record}'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
