<?php

namespace App\Filament\Resources\Panel\PlnChargerLocationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'plnChargerLocationDetails';

    protected static ?string $recordTitleAttribute = 'chargebox_name';

    protected static ?string $title = 'Detail Charger';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chargebox_id')
                    ->label('Chargebox ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chargebox_name')
                    ->label('Nama Chargebox')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('power')
                    ->label('Daya')
                    ->sortable(),
                TextColumn::make('is_active_charger')
                    ->label('Aktif')
                    ->sortable(),
                TextColumn::make('count_connector_charger')
                    ->label('Konektor')
                    ->alignCenter(),
                TextColumn::make('operation_date')
                    ->label('Tanggal Operasi')
                    ->date()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('chargerCategory.name')
                    ->label('Kategori Charger')
                    ->sortable(),
                TextColumn::make('merkCharger.name')
                    ->label('Merk Charger')
                    ->sortable(),
            ]);
    }
}
