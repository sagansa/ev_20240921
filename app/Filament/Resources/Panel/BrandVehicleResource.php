<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BrandVehicle;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\BrandVehicleResource\Pages;
use App\Filament\Resources\Panel\BrandVehicleResource\RelationManagers;

class BrandVehicleResource extends Resource
{
    protected static ?string $model = BrandVehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Vehicles';

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
                        ->string()
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
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('name'),
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
