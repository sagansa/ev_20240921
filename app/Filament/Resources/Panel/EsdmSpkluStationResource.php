<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\EsdmSpkluStationResource\Pages;
use App\Filament\Resources\Panel\EsdmSpkluStationResource\RelationManagers\InstallationsRelationManager;
use App\Models\EsdmSinggatSpkluStation;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EsdmSpkluStationResource extends Resource
{
    protected static ?string $model = EsdmSinggatSpkluStation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Provider & SPKLU';

    protected static ?string $navigationLabel = 'ESDM SPKLU (Raw)';

    protected static ?string $modelLabel = 'Stasiun ESDM';

    protected static ?string $pluralModelLabel = 'ESDM SPKLU (Raw)';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Data Stasiun')->schema([
                Grid::make(2)->schema([
                    Placeholder::make('esdm_id')
                        ->label('ESDM ID')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => '#'.$record->esdm_id),
                    Placeholder::make('nama_stasiun')
                        ->label('Nama Stasiun')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->nama_stasiun),
                    Placeholder::make('nama_badan_usaha')
                        ->label('Badan Usaha')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->nama_badan_usaha ?? '—'),
                    Placeholder::make('alamat_spklu')
                        ->label('Alamat')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->alamat_spklu ?? '—')
                        ->columnSpanFull(),
                ]),
            ]),

            Section::make('Geolokasi')->schema([
                Grid::make(3)->schema([
                    Placeholder::make('latitude_spklu_raw')
                        ->label('Latitude (Raw)')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->latitude_spklu_raw ?? '—'),
                    Placeholder::make('longitude_spklu_raw')
                        ->label('Longitude (Raw)')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->longitude_spklu_raw ?? '—'),
                    Placeholder::make('geo_status')
                        ->label('Status Geo')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->geo_status ?? '—'),
                    Placeholder::make('latitude')
                        ->label('Latitude (Cleaned)')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->latitude ?? '—'),
                    Placeholder::make('longitude')
                        ->label('Longitude (Cleaned)')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->longitude ?? '—'),
                    Placeholder::make('kode_provinsi')
                        ->label('Kode Provinsi')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->kode_provinsi ?? '—'),
                    Placeholder::make('geo_notes')
                        ->label('Catatan Geo')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->geo_notes ?? '—')
                        ->columnSpanFull(),
                ]),
            ]),

            Section::make('Metadata Import')->schema([
                Grid::make(3)->schema([
                    Placeholder::make('import_batch')
                        ->label('Import Batch')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->import_batch ?? '—'),
                    Placeholder::make('created_at')
                        ->label('Diimpor')
                        ->content(fn (EsdmSinggatSpkluStation $record): string => $record->created_at?->format('d M Y H:i') ?? '—'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('esdm_id')
                    ->label('ESDM ID')
                    ->sortable(),
                TextColumn::make('nama_stasiun')
                    ->label('Nama Stasiun')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                // Kolom virtual: nama provinsi di-derive dari kode_provinsi (BPS map).
                // TIDAK searchable/sortable — itu akan diterjemahkan ke SQL pada kolom
                // "provinsi" yang tidak ada di tabel raw ESDM. Filter provinsi sudah
                // tersedia via SelectFilter kode_provinsi di bawah (yg map kode→nama).
                TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->getStateUsing(fn (EsdmSinggatSpkluStation $record): string => \App\Services\CanonicalStationHydrateService::PROVINCE_BY_BPS_CODE[$record->kode_provinsi] ?? $record->kode_provinsi ?? '—')
                    ->placeholder('—'),
                TextColumn::make('nama_badan_usaha')
                    ->label('Badan Usaha')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('count_konektor')
                    ->label('Konektor')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('latitude_spklu_raw')
                    ->label('Lat Raw')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 4),
                TextColumn::make('geo_status')
                    ->label('Geo')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'swapped' => 'warning',
                        'fixed_digits' => 'info',
                        'unresolved' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('import_batch')
                    ->label('Import Batch')
                    ->searchable()
                    ->sortable()
                    ->limit(12),
                TextColumn::make('created_at')
                    ->label('Diimpor')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kode_provinsi')
                    ->label('Filter Provinsi')
                    ->options(fn () => EsdmSinggatSpkluStation::query()
                        ->whereNotNull('kode_provinsi')
                        ->distinct()
                        ->pluck('kode_provinsi', 'kode_provinsi')
                        ->mapWithKeys(fn (string $code): array => [$code => \App\Services\CanonicalStationHydrateService::PROVINCE_BY_BPS_CODE[$code] ?? $code])
                        ->toArray())
                    ->searchable(),
                SelectFilter::make('geo_status')
                    ->label('Filter Status Geo')
                    ->options([
                        'ok' => 'ok',
                        'swapped' => 'swapped',
                        'fixed_digits' => 'fixed_digits',
                        'unresolved' => 'unresolved',
                    ]),
                SelectFilter::make('nama_badan_usaha')
                    ->label('Filter Badan Usaha')
                    ->options(fn () => EsdmSinggatSpkluStation::query()->whereNotNull('nama_badan_usaha')->distinct()->pluck('nama_badan_usaha', 'nama_badan_usaha')->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                ]),
            ])
            ->defaultSort('esdm_id', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            InstallationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEsdmSpkluStations::route('/'),
            'view' => Pages\ViewEsdmSpkluStation::route('/{record}'),
        ];
    }
}
