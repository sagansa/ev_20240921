<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use App\Filament\Resources\Panel\BrandVehicleResource\Pages;
use App\Filament\Resources\Panel\BrandVehicleResource\RelationManagers;
use App\Models\BrandVehicle;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandVehicleResource extends Resource
{
    protected static ?string $model = BrandVehicle::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Referensi Kendaraan';
    }

    public static function getModelLabel(): string
    {
        return __('crud.brandVehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.brandVehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.brandVehicles.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Informasi Brand / Merek')
                ->description('Kelola data induk merek kendaraan.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        ImageFileUpload::make('image')
                            ->directory('images/brand')
                            ->image()
                            ->label('Logo Brand'),

                        TextInput::make('name')
                            ->required()
                            ->string()
                            ->label('Nama Brand')
                            ->placeholder('cth. Wuling, BYD, Hyundai, Chery, MG')
                            ->autofocus(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Brand&color=10b981&background=ecfdf5')
                    ->visibility('public'),

                TextColumn::make('name')
                    ->label('Nama Brand')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('model_vehicles_count')
                    ->counts('modelVehicles')
                    ->label('Jumlah Model')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ModelVehiclesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrandVehicles::route('/'),
            'create' => Pages\CreateBrandVehicle::route('/create'),
            'view' => Pages\ViewBrandVehicle::route('/{record}'),
            'edit' => Pages\EditBrandVehicle::route('/{record}/edit'),
        ];
    }
}