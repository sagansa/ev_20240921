<?php

namespace App\Filament\Resources\Panel\EsdmSpkluStationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallationsRelationManager extends RelationManager
{
    protected static string $relationship = 'installations';

    protected static ?string $recordTitleAttribute = 'merek_mesin';

    protected static ?string $title = 'Instalasi';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('esdm_id')
                    ->label('ESDM ID')
                    ->sortable(),
                TextColumn::make('nomor_identitas')
                    ->label('Nomor Identitas')
                    ->searchable(),
                TextColumn::make('merek_mesin')
                    ->label('Merek Mesin')
                    ->searchable(),
                TextColumn::make('jenis_pengisian_spklu')
                    ->label('Jenis Pengisian'),
                TextColumn::make('harga_pengisian_raw')
                    ->label('Harga Pengisian'),
                TextColumn::make('harga_layanan_raw')
                    ->label('Harga Layanan'),
                TextColumn::make('connectors_count')
                    ->label('Konektor')
                    ->counts('connectors')
                    ->alignCenter(),
            ])
            ->filters([])
            ->recordActions([])
            ->defaultSort('id');
    }
}
