<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\CheckboxColumn;
use App\Filament\Resources\Panel\ChargerLocationResource\Pages;
use App\Filament\Resources\Panel\ChargerLocationResource\RelationManagers;

class ChargerLocationResource extends Resource
{
    protected static ?string $model = ChargerLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    FileUpload::make('image')
                        ->rules(['image'])
                        ->nullable()
                        ->maxSize(1024)
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']),

                    TextInput::make('name')
                        ->required()
                        ->string(),

                    Select::make('provider_id')
                        ->required()
                        ->relationship('provider', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('latitude')
                        ->nullable()
                        ->numeric()
                        ->step(1),

                    TextInput::make('longitude')
                        ->nullable()
                        ->numeric()
                        ->step(1),

                    Checkbox::make('parking')
                        ->rules(['boolean'])
                        ->required()
                        ->inline(),

                    Select::make('province_id')
                        ->required()
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('city_id')
                        ->required()
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('district_id')
                        ->nullable()
                        ->relationship('district', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('subdistrict_id')
                        ->nullable()
                        ->relationship('subdistrict', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('postal_code_id')
                        ->nullable()
                        ->relationship('postalCode', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('status')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options([
                            '1' => 'not verified',
                            '2' => 'verified',
                            '3' => 'closed',
                        ]),

                    Select::make('location_on')
                        ->required()
                        ->default('1')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options([
                            '1' => 'public',
                            '2' => 'private',
                            '3' => 'dealer',
                            '4' => 'closed',
                        ]),

                    RichEditor::make('description')
                        ->nullable()
                        ->string()
                        ->fileAttachmentsVisibility('public'),
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

                TextColumn::make('name'),

                TextColumn::make('provider.name'),

                TextColumn::make('latitude'),

                TextColumn::make('longitude'),

                CheckboxColumn::make('parking'),

                TextColumn::make('province.name'),

                TextColumn::make('city.name'),

                TextColumn::make('status'),

                TextColumn::make('location_on'),

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
            'index' => Pages\ListChargerLocations::route('/'),
            'create' => Pages\CreateChargerLocation::route('/create'),
            'view' => Pages\ViewChargerLocation::route('/{record}'),
            'edit' => Pages\EditChargerLocation::route('/{record}/edit'),
        ];
    }
}
