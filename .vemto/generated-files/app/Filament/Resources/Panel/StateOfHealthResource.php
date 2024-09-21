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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Panel\StateOfHealthResource\Pages;
use App\Filament\Resources\Panel\StateOfHealthResource\RelationManagers;

class StateOfHealthResource extends Resource
{
    protected static ?string $model = StateOfHealth::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                        ->required()
                        ->relationship('vehicle', 'id')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    TextInput::make('km')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('km')
                        ->inputMode('numeric'),

                    TextInput::make('percentage')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->suffix('%')
                        ->inputMode('numeric'),

                    TextInput::make('remaining_battery')
                        ->nullable()
                        ->numeric()
                        ->step(1)
                        ->suffix('kWh')
                        ->inputMode('numeric'),
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

                TextColumn::make('vehicle.id'),

                TextColumn::make('km'),

                TextColumn::make('percentage'),

                TextColumn::make('remaining_battery'),

                TextColumn::make('user.name'),
            ])
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
