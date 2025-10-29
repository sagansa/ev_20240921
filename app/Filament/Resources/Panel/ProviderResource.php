<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\ImageFileUpload;
use Filament\Tables;
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
use App\Filament\Resources\Panel\ProviderResource\Pages;
use App\Filament\Resources\Panel\ProviderResource\RelationManagers;
use App\Tables\Columns\StatusActiveColumn;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\ActionGroup;
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
            Group::make()->schema([
                Section::make()
                    ->schema(static::getContactFormSchema()),

                Section::make()
                    ->schema(static::getCostFormSchema()),

            ])->columnSpan(['lg' => 1]),

            Group::make()->schema([
                Section::make()
                    ->schema(static::getAppsFormSchema()),

                Section::make()
                    ->schema(static::getAddressFormSchema()),

            ])->columnSpan(['lg' => 1]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')
                    ->visibility('public'),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('contact')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('price'),

                TextColumn::make('admin_fee'),

                TextColumn::make('tax'),

                Tables\Columns\IconColumn::make('public')
                    ->boolean(),

                StatusActiveColumn::make('status')
                    ->visible(fn() => auth()->user() instanceof \App\Models\User && auth()->user()->hasRole('super_admin')),
            ])
            ->filters([
                // Tambahkan filter jika diperlukan
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
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

    public static function getAddressFormSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                TextInput::make('address')
                    ->nullable()
                    ->string(),

                BaseSelect::make('province_id')
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
            ])
        ];
    }

    public static function getCostFormSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                TextInput::make('price'),

                TextInput::make('admin_fee'),

                TextInput::make('tax'),
            ])
        ];
    }

    public static function getContactFormSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                ImageFileUpload::make('image')
                    ->directory('images/provider'),

                TextInput::make('name')
                    ->required()
                    ->string(),

                TextInput::make('contact')
                    ->nullable()
                    ->string(),

                TextInput::make('email')
                    ->nullable()
                    ->email(),

                Select::make('status')
                    ->required()
                    ->searchable()
                    ->options([
                        '1' => 'active',
                        '2' => 'inactive',
                    ]),

                Toggle::make('public')
                    ->default(true)
                    ->inlineLabel(),
            ])
        ];
    }

    public static function getAppsFormSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                TextInput::make('web')
                    ->nullable()
                    ->url(),

                TextInput::make('google')
                    ->nullable()
                    ->url(),

                TextInput::make('ios')
                    ->nullable()
                    ->url(),
            ])
        ];
    }
}
