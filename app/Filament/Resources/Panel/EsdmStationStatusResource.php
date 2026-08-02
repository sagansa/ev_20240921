<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Concerns\AvailabilityLevelColors;
use App\Filament\Resources\Panel\EsdmStationStatusResource\Pages;
use App\Models\EsdmSinggatStationStatus;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EsdmStationStatusResource extends Resource
{
    use AvailabilityLevelColors;

    protected static ?string $model = EsdmSinggatStationStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|\UnitEnum|null $navigationGroup = 'Provider & SPKLU';

    protected static ?string $navigationLabel = 'ESDM Status Real-time';

    protected static ?string $modelLabel = 'Status Stasiun';

    protected static ?string $pluralModelLabel = 'ESDM Status Real-time';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('station.nama_stasiun')
                    ->label('Nama Stasiun')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('availability_level')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::availabilityLevelLabel($state))
                    ->color(fn (string $state): string => static::availabilityLevelColor($state)),
                TextColumn::make('total_connectors')
                    ->label('Total')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('available_count')
                    ->label('Siap')
                    ->alignCenter()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('charging_count')
                    ->label('Mengisi')
                    ->alignCenter()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('finishing_count')
                    ->label('Menunggu')
                    ->alignCenter()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('unavailable_count')
                    ->label('Offline')
                    ->alignCenter(),
                TextColumn::make('unknown_count')
                    ->label('Unknown')
                    ->alignCenter(),
                TextColumn::make('aggregated_at')
                    ->label('Agregasi')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('availability_level')
                    ->label('Filter Status')
                    ->options([
                        'available' => 'Tersedia',
                        'partial' => 'Sebagian',
                        'occupied' => 'Penuh',
                        'offline' => 'Offline',
                        'unknown' => 'Tidak Diketahui',
                    ]),
            ])
            ->recordActions([])
            ->defaultSort('aggregated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEsdmStationStatuses::route('/'),
        ];
    }
}
