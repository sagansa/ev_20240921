<?php

namespace App\Filament\Resources\Panel\EsdmSpbkluStationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CabinetsRelationManager extends RelationManager
{
    protected static string $relationship = 'cabinets';

    protected static ?string $recordTitleAttribute = 'merek_kabinet';

    protected static ?string $title = 'Kabinet';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('esdm_id')
                    ->label('ESDM ID')
                    ->sortable(),
                TextColumn::make('merek_kabinet')
                    ->label('Merek Kabinet')
                    ->searchable(),
                TextColumn::make('status_instalasi')
                    ->label('Status Instalasi'),
                TextColumn::make('kapasitas_raw')
                    ->label('Kapasitas'),
                TextColumn::make('harga_penukaran_baterai_raw')
                    ->label('Harga Penukaran'),
                TextColumn::make('batteries_count')
                    ->label('Baterai')
                    ->counts('batteries')
                    ->alignCenter(),
            ])
            ->filters([])
            ->recordActions([])
            ->defaultSort('id');
    }
}
