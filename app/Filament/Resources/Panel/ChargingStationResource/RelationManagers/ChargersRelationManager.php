<?php

namespace App\Filament\Resources\Panel\ChargingStationResource\RelationManagers;

use App\Filament\Concerns\TypeChargeColors;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChargersRelationManager extends RelationManager
{
    use TypeChargeColors;

    protected static string $relationship = 'chargers';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $title = 'Charger Boxes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('type_charge')
                    ->label('Tier')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => static::typeChargeColor($state)),
                TextColumn::make('watt')
                    ->label('Watt')
                    ->alignCenter(),
                TextColumn::make('jumlah_konektor')
                    ->label('Konektor')
                    ->alignCenter(),
                TextColumn::make('availability_level')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => \App\Filament\Concerns\AvailabilityLevelColors::availabilityLevelLabel($state))
                    ->color(fn (string $state): string => \App\Filament\Concerns\AvailabilityLevelColors::availabilityLevelColor($state)),
                TextColumn::make('available_count')
                    ->label('Siap/Konektor')
                    ->getStateUsing(fn ($record): string => $record->available_count.'/'.$record->jumlah_konektor)
                    ->alignCenter(),
                TextColumn::make('status_updated_at')
                    ->label('Update')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->defaultSort('id');
    }
}
