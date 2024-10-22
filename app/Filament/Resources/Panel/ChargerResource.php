<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\ChargerResource\Pages;
use App\Filament\Resources\ChargerResource\RelationManagers;
use App\Models\Charger;
use App\Tables\Columns\StatusLocationColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChargerResource extends Resource
{
    protected static ?string $model = Charger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Apps';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chargerLocation.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chargerLocation.provider.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currentCharger.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('typeCharger.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('powerCharger.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chargerLocation.city.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('chargerLocation.province.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                StatusLocationColumn::make('status')
                    ->sortable(),

                TextColumn::make('user.name')
                    // ->visible(fn($record) => auth()->user()->hasRole('super_admin'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('chargerLocation.provider')
                    ->relationship('chargerLocation.provider', 'name'),
                SelectFilter::make('currentCharger')
                    ->relationship('currentCharger', 'name'),
                SelectFilter::make('typeCharger')
                    ->relationship('typeCharger', 'name'),
                SelectFilter::make('powerCharger')
                    ->relationship('powerCharger', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChargers::route('/'),
            'create' => Pages\CreateCharger::route('/create'),
            'view' => Pages\ViewCharger::route('/{record}'),
            'edit' => Pages\EditCharger::route('/{record}/edit'),
        ];
    }
}
