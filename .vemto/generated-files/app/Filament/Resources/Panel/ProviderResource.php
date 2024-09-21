<?php

namespace App\Filament\Resources\Panel;

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

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Chargers';

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

                    TextInput::make('contact')
                        ->nullable()
                        ->string(),

                    TextInput::make('address')
                        ->nullable()
                        ->string(),

                    Select::make('province_id')
                        ->nullable()
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('city_id')
                        ->nullable()
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

                TextColumn::make('name'),

                TextColumn::make('contact'),

                TextColumn::make('address'),

                TextColumn::make('province.name'),

                TextColumn::make('city.name'),

                TextColumn::make('district.name'),

                TextColumn::make('subdistrict.name'),

                TextColumn::make('postalCode.name'),

                TextColumn::make('status'),
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
