<?php

namespace App\Filament\Resources\Panel;

use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use App\Models\User;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\UserResource\Pages;
use App\Filament\Resources\Panel\UserResource\RelationManagers;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-s-user-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Admin';




    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-s-user-group';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Admin';
    }

    public static function getModelLabel(): string
    {
        return __('crud.users.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.users.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.users.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->autofocus(),

                    TextInput::make('email')
                        ->required()
                        ->string()
                        ->unique('users', 'email', ignoreRecord: true)
                        ->email(),

                    TextInput::make('password')
                        ->required(
                            fn(string $context): bool => $context === 'create'
                        )
                        ->dehydrated(fn($state) => filled($state))
                        ->string()
                        ->minLength(6)
                        ->password(),

                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->searchable()
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')->searchable(),

                TextColumn::make('email')->searchable(),

                TextColumn::make('roles.name'),



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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
