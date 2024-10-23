<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\ChargerResource\Pages;
use App\Filament\Resources\ChargerResource\RelationManagers;
use App\Models\Charger;
use App\Models\PowerCharger;
use App\Models\TypeCharger;
use App\Tables\Columns\StatusLocationColumn;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\BulkAction;

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
                Group::make()->schema([
                    Section::make()->schema([
                        Select::make('charger_location_id')
                            ->relationship('chargerLocation', 'name', function (Builder $query) {
                                return $query->with('provider');
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $chargerLocation = \App\Models\ChargerLocation::with('provider')->find($value);
                                if (!$chargerLocation) {
                                    return null;
                                }
                                return "{$chargerLocation->name} - {$chargerLocation->provider->name}";
                            })
                            ->inlineLabel()
                            ->required()
                            ->searchable(['name', 'provider.name']),

                        Select::make('current_charger_id')
                            ->relationship('currentCharger', 'name')
                            ->inlineLabel()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('type_charger_id', null);
                                $set('power_charger_id', null);
                            }),

                        Select::make('type_charger_id')
                            ->relationship('typeCharger', 'name')
                            ->inlineLabel()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('power_charger_id', null))
                            ->options(function (Get $get) {
                                $currentChargerId = $get('current_charger_id');
                                if (!$currentChargerId) {
                                    return Collection::empty();
                                }
                                return TypeCharger::where('current_charger_id', $currentChargerId)->pluck('name', 'id');
                            }),

                        Select::make('power_charger_id')
                            ->relationship('powerCharger', 'name')
                            ->inlineLabel()
                            ->required()
                            ->options(function (Get $get) {
                                $typeChargerId = $get('type_charger_id');
                                if (!$typeChargerId) {
                                    return Collection::empty();
                                }
                                return PowerCharger::where('type_charger_id', $typeChargerId)->pluck('name', 'id');
                            }),

                        TextInput::make('unit')
                            ->inlineLabel()
                            ->required()
                            ->numeric(),
                    ])
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $chargers = Charger::query();

        if (Auth::user()->hasRole('user')) {
            $chargers->where('user_id', Auth::id());
        }

        return $table
            ->query($chargers)
            ->poll('60s')
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
                    ViewAction::make(),
                    EditAction::make()
                        ->hidden(function ($record) {
                            $user = Auth::user();
                            // Sembunyikan jika user bukan admin dan status charger adalah 2
                            return !$user->hasRole('admin') && $record->status === 2;
                        }),
                ]),
            ])
            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('updateStatus')
                        ->label('Change Status to Verified')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 2]);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->color('success')
                        ->icon('heroicon-o-exclamation-circle'),

                    BulkAction::make('updateStatus')
                        ->label('Change Status to Closed')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 3]);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->color('gray')
                        ->icon('heroicon-o-exclamation-circle'),
                ])
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
