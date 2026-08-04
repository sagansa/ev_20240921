<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\PlnEsdmStationMatchResource\Pages;
use App\Filament\Resources\Panel\PlnEsdmStationMatchResource\RelationManagers;
use App\Models\ChargingStation;
use App\Models\PlnEsdmStationMatch;
use App\Services\PlnEsdmMatchService;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Review matching PLN ↔ ESDM — ditampilkan per-PLN (1 baris = 1 stasiun PLN).
 *
 * Klik baris → halaman View menampilkan SEMUA kandidat ESDM (RelationManager),
 * admin pilih satu sebagai pemenang (approve). Sisa kandidat otomatis di-reject
 * oleh PlnEsdmMatchService::approve() ("Superseded").
 *
 * Filter "Perlu pilih pemenang" = PLN yang punya kandidat pending/ai_suggested
 * tapi belum ada approved. PLN yang sudah punya pemenang (approved) dianggap
 * selesai dan disembunyikan dari review default.
 */
class PlnEsdmStationMatchResource extends Resource
{
    protected static ?string $model = ChargingStation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Matching PLN-ESDM';

    protected static ?string $navigationLabel = 'Review Match PLN↔ESDM';

    protected static ?string $modelLabel = 'Match PLN↔ESDM';

    protected static ?string $pluralModelLabel = 'Review Match PLN↔ESDM';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    /** Hanya PLN yang punya ≥1 kandidat match. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('source', 'pln')
            ->whereHas('plnEsdmMatches');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->defaultPaginationPageOptions([25, 50, 100])
            ->columns([
                TextColumn::make('nama_lokasi')
                    ->label('Stasiun PLN')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->limit(20),
                TextColumn::make('pln_esdm_matches_count')
                    ->label('Kandidat')
                    ->counts('plnEsdmMatches')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('winner')
                    ->label('Pemenang (ESDM)')
                    ->getStateUsing(function (ChargingStation $record): ?string {
                        $winner = $record->plnEsdmMatches->firstWhere('match_status', PlnEsdmMatchService::STATUS_APPROVED);
                        if ($winner === null) {
                            return null;
                        }

                        return $winner->esdm_name ?? $winner->esdmStation?->nama_lokasi;
                    })
                    ->placeholder('— belum dipilih —')
                    ->limit(40),
                TextColumn::make('has_winner')
                    ->label('Status')
                    ->getStateUsing(fn (ChargingStation $record): string => $record->plnEsdmMatches->contains('match_status', PlnEsdmMatchService::STATUS_APPROVED) ? 'Sudah dipilih' : 'Perlu pilih')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sudah dipilih' ? 'success' : 'warning'),
            ])
            ->filters([
                TernaryFilter::make('has_winner')
                    ->label('Status pemenang')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah punya pemenang')
                    ->falseLabel('Perlu pilih (belum ada approved)')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('plnEsdmMatches', fn ($q) => $q->where('match_status', PlnEsdmMatchService::STATUS_APPROVED)),
                        false: fn (Builder $query) => $query->whereDoesntHave('plnEsdmMatches', fn ($q) => $q->where('match_status', PlnEsdmMatchService::STATUS_APPROVED)),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Actions\Action::make('pilih_pemenang')
                    ->label('Pilih Pemenang')
                    ->icon('heroicon-o-trophy')
                    ->color('warning')
                    ->visible(fn (ChargingStation $record): bool => ! $record->plnEsdmMatches->contains('match_status', PlnEsdmMatchService::STATUS_APPROVED))
                    ->url(fn (ChargingStation $record): string => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),
                Actions\ViewAction::make()
                    ->label('Lihat Kandidat')
                    ->icon('heroicon-o-eye'),
            ])
            ->recordUrl(fn (ChargingStation $record): string => static::getUrl('view', ['record' => $record]))
            ->defaultSort('nama_lokasi');
    }

    /**
     * Skema perbandingan side-by-side PLN vs ESDM + skor + AI reasoning.
     * Dipakai di modal approve & reject di RelationManager.
     */
    public static function comparisonSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('PLN (sumber master)')->schema([
                    Placeholder::make('pln_name')
                        ->label('Nama')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->pln_name),
                    Placeholder::make('pln_address')
                        ->label('Alamat')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) ($record->plnStation?->alamat ?? '—')),
                    Placeholder::make('pln_coord')
                        ->label('Koordinat')
                        ->content(fn (PlnEsdmStationMatch $record): string => $record->plnStation
                            ? $record->plnStation->latitude.', '.$record->plnStation->longitude
                            : '—'),
                    Placeholder::make('pln_maps')
                        ->label('Google Maps')
                        ->content(fn (PlnEsdmStationMatch $record): \Illuminate\Support\HtmlString => static::mapsLinkHtml(
                            $record->plnStation?->latitude,
                            $record->plnStation?->longitude,
                            'Buka lokasi PLN',
                        )),
                ]),
                Section::make('ESDM (kandidat pemenang)')->schema([
                    Placeholder::make('esdm_name')
                        ->label('Nama')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->esdm_name),
                    Placeholder::make('esdm_address')
                        ->label('Alamat')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) ($record->esdmStation?->alamat ?? '—')),
                    Placeholder::make('esdm_coord')
                        ->label('Koordinat')
                        ->content(fn (PlnEsdmStationMatch $record): string => $record->esdmStation
                            ? $record->esdmStation->latitude.', '.$record->esdmStation->longitude
                            : '—'),
                    Placeholder::make('esdm_maps')
                        ->label('Google Maps')
                        ->content(fn (PlnEsdmStationMatch $record): \Illuminate\Support\HtmlString => static::mapsLinkHtml(
                            $record->esdmStation?->latitude,
                            $record->esdmStation?->longitude,
                            'Buka lokasi ESDM',
                        )),
                ]),
            ]),
            Section::make('Skor Matching')->schema([
                Grid::make(3)->schema([
                    Placeholder::make('distance_display')
                        ->label('Jarak (haversine)')
                        ->content(fn (PlnEsdmStationMatch $record): string => $record->distance_m !== null
                            ? number_format($record->distance_m, 0, ',', '.').' m'
                            : '—'),
                    Placeholder::make('similarity_display')
                        ->label('Kemiripan Nama')
                        ->content(fn (PlnEsdmStationMatch $record): string => $record->similarity_pct !== null
                            ? $record->similarity_pct.'%'
                            : '—'),
                    Placeholder::make('confidence_display')
                        ->label('AI Confidence')
                        ->content(fn (PlnEsdmStationMatch $record): string => $record->ai_confidence !== null
                            ? $record->ai_confidence.'%'
                            : '—'),
                ]),
            ]),
            Section::make('AI Reasoning (audit)')->schema([
                Placeholder::make('ai_reasoning_display')
                    ->label('Alasan AI')
                    ->content(fn (PlnEsdmStationMatch $record): \Illuminate\Support\HtmlString => static::aiReasoningHtml($record))
                    ->columnSpanFull(),
            ]),
        ];
    }

    public static function mapsLinkHtml(?float $lat, ?float $lng, string $label): \Illuminate\Support\HtmlString
    {
        if ($lat === null || $lng === null) {
            return new \Illuminate\Support\HtmlString('—');
        }

        $url = 'https://www.google.com/maps?q='.rawurlencode((string) $lat.','.$lng);

        return new \Illuminate\Support\HtmlString(
            '<a href="'.$url.'" target="_blank" rel="noopener" style="color:#0ea5e9">'.$label.' ↗</a>'
        );
    }

    public static function aiReasoningHtml(PlnEsdmStationMatch $record): \Illuminate\Support\HtmlString
    {
        $ai = $record->ai_reasoning;
        if (empty($ai)) {
            return new \Illuminate\Support\HtmlString('—');
        }

        $lines = [];

        if (isset($ai['error'])) {
            $lines[] = '<span style="color:#dc2626">⚠ Error AI: '.e($ai['error']).'</span>';
        }
        if (array_key_exists('match', $ai)) {
            $lines[] = '<b>match:</b> '.($ai['match'] ? 'true' : 'false');
        }
        if (isset($ai['confidence'])) {
            $lines[] = '<b>confidence:</b> '.$ai['confidence'];
        }
        if (isset($ai['reason'])) {
            $lines[] = '<b>reason:</b> '.e($ai['reason']);
        }
        if (! empty($ai['signals']) && is_array($ai['signals'])) {
            $lines[] = '<b>signals:</b> '.e(implode(', ', $ai['signals']));
        }
        if (isset($ai['note'])) {
            $lines[] = '<i>'.e($ai['note']).'</i>';
        }

        if ($lines === []) {
            return new \Illuminate\Support\HtmlString('—');
        }

        return new \Illuminate\Support\HtmlString('<div style="line-height:1.7">'.implode('<br>', $lines).'</div>');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CandidatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlnEsdmStationMatches::route('/'),
            'view' => Pages\ViewPlnEsdmStationMatch::route('/{record}'),
        ];
    }
}
