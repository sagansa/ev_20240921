<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\SpkluLocationResource\Pages;
use App\Models\Provider;
use App\Models\SpkluLocation;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SpkluLocationResource extends Resource
{
    protected static ?string $model = SpkluLocation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string | \UnitEnum | null $navigationGroup = 'JSON SPKLU';

    protected static ?string $navigationLabel = 'List SPKLU Data';

    protected static ?string $modelLabel = 'SPKLU Location';

    protected static ?string $pluralModelLabel = 'List SPKLU Data';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('nama_lokasi')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                SelectColumn::make('provider_id')
                    ->label('Provider EV')
                    ->options(fn () => Provider::pluck('name', 'id')->toArray())
                    ->placeholder('Belum terhubung')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kabupaten_kota')
                    ->label('Kabupaten/Kota')
                    ->searchable(),
                TextColumn::make('type_charge')
                    ->label('Type Charge')
                    ->sortable(),
                TextColumn::make('watt')
                    ->label('Watt'),
                TextColumn::make('total_charger')
                    ->label('Charger')
                    ->alignCenter(),
                TextColumn::make('total_konektor')
                    ->label('Konektor')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('provider_id')
                    ->label('Filter Provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('provinsi')
                    ->label('Filter Provinsi')
                    ->options(fn () => SpkluLocation::distinct()->pluck('provinsi', 'provinsi')->toArray()),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('assignProvider')
                        ->label('Hubungkan Provider ke Data Terpilih (Bulk)')
                        ->icon('heroicon-o-link')
                        ->form([
                            Select::make('provider_id')
                                ->label('Pilih Provider EV')
                                ->options(fn () => Provider::pluck('name', 'id')->toArray())
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (SpkluLocation $record) => $record->update(['provider_id' => $data['provider_id']]));

                            Notification::make()
                                ->title('Provider berhasil diperbarui untuk seluruh data terpilih')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpkluLocations::route('/'),
        ];
    }
}
