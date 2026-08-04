<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\PlnEsdmStationMatchResource\Pages;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PlnEsdmStationMatchResource extends Resource
{
    protected static ?string $model = PlnEsdmStationMatch::class;

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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('plnStation.nama_lokasi')
                    ->label('PLN')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(35),
                TextColumn::make('esdmStation.nama_lokasi')
                    ->label('ESDM')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                TextColumn::make('distance_m')
                    ->label('Jarak')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? number_format($state, 0, ',', '.').' m' : '—')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('similarity_pct')
                    ->label('Kemiripan Nama')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? $state.'%' : '—')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 85 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter(),
                TextColumn::make('ai_confidence')
                    ->label('AI Confidence')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? $state.'%' : '—')
                    ->placeholder('—')
                    ->alignCenter(),
                TextColumn::make('match_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'auto_geo_name' => 'success',
                        'auto_geo' => 'info',
                        'ai' => 'warning',
                        'manual' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('match_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'ai_suggested' => 'warning',
                        'pending' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('decided_by')
                    ->label('Diputus oleh')
                    ->placeholder('—')
                    ->limit(20),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('match_status')
                    ->label('Filter Status')
                    ->options([
                        'pending' => 'Pending (review)',
                        'ai_suggested' => 'AI suggest (review)',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected (admin)',
                        'rejected_ai' => 'Rejected (AI)',
                    ]),
                TernaryFilter::make('perlu_review')
                    ->label('Perlu Review')
                    ->placeholder('Semua')
                    ->trueLabel('Perlu review (pending + AI suggest)')
                    ->falseLabel('Sudah final')
                    ->queries(
                        true: fn ($query) => $query->whereIn('match_status', ['pending', 'ai_suggested']),
                        false: fn ($query) => $query->whereNotIn('match_status', ['pending', 'ai_suggested']),
                        blank: fn ($query) => $query,
                    ),
                SelectFilter::make('match_method')
                    ->label('Filter Metode')
                    ->options([
                        'auto_geo' => 'auto_geo',
                        'auto_geo_name' => 'auto_geo_name',
                        'ai' => 'ai',
                        'manual' => 'manual',
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\Action::make('approve')
                        ->label('Approve Match')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (PlnEsdmStationMatch $record): bool => $record->match_status !== 'approved')
                        ->modalHeading('Approve Match PLN ↔ ESDM')
                        ->modalSubmitActionLabel('Approve')
                        ->form(static::comparisonSchema())
                        ->action(function (PlnEsdmStationMatch $record): void {
                            app(PlnEsdmMatchService::class)->approve($record->id, Auth::user()?->email);

                            Notification::make()
                                ->title('Match di-approve')
                                ->body('Status stasiun PLN kini mengikuti status real-time ESDM.')
                                ->success()
                                ->send();
                        }),
                    Actions\Action::make('reject')
                        ->label('Tolak Match')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (PlnEsdmStationMatch $record): bool => $record->match_status !== 'rejected')
                        ->modalHeading('Tolak Match PLN ↔ ESDM')
                        ->modalSubmitActionLabel('Tolak')
                        ->form([
                            ...static::comparisonSchema(),
                            Textarea::make('rejected_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(3)
                                ->columnSpanFull()
                                ->helperText('Alasan wajib diisi untuk audit.'),
                        ])
                        ->action(function (PlnEsdmStationMatch $record, array $data): void {
                            app(PlnEsdmMatchService::class)->reject(
                                $record->id,
                                $data['rejected_reason'] ?? null,
                                Auth::user()?->email,
                            );

                            Notification::make()
                                ->title('Match ditolak')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('bulkApprove')
                        ->label('Approve Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(PlnEsdmMatchService::class);
                            $user = Auth::user()?->email;
                            foreach ($records as $record) {
                                $service->approve($record->id, $user);
                            }

                            Notification::make()
                                ->title(count($records).' match di-approve')
                                ->body('Status stasiun PLN mengikuti status ESDM.')
                                ->success()
                                ->send();
                        }),
                    Actions\BulkAction::make('bulkReject')
                        ->label('Tolak Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Textarea::make('rejected_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(3)
                                ->helperText('Alasan yang sama diterapkan ke semua match terpilih.'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $service = app(PlnEsdmMatchService::class);
                            $user = Auth::user()?->email;
                            foreach ($records as $record) {
                                $service->reject($record->id, $data['rejected_reason'] ?? null, $user);
                            }

                            Notification::make()
                                ->title(count($records).' match ditolak')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * Skema perbandingan side-by-side PLN vs ESDM + skor + AI reasoning.
     * Dipakai di modal approve & reject.
     */
    public static function comparisonSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('PLN (sumber status kosong)')->schema([
                    Placeholder::make('pln_name')
                        ->label('Nama')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->pln_name),
                    Placeholder::make('pln_address')
                        ->label('Alamat')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->plnStation?->alamat ?? '—'),
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
                Section::make('ESDM (sumber status)')->schema([
                    Placeholder::make('esdm_name')
                        ->label('Nama')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->esdm_name),
                    Placeholder::make('esdm_address')
                        ->label('Alamat')
                        ->content(fn (PlnEsdmStationMatch $record): string => (string) $record->esdmStation?->alamat ?? '—'),
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

    private static function mapsLinkHtml(?float $lat, ?float $lng, string $label): \Illuminate\Support\HtmlString
    {
        if ($lat === null || $lng === null) {
            return new \Illuminate\Support\HtmlString('—');
        }

        $url = 'https://www.google.com/maps?q='.rawurlencode((string) $lat.','.$lng);

        return new \Illuminate\Support\HtmlString(
            '<a href="'.$url.'" target="_blank" rel="noopener" style="color:#0ea5e9">'.$label.' ↗</a>'
        );
    }

    private static function aiReasoningHtml(PlnEsdmStationMatch $record): \Illuminate\Support\HtmlString
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlnEsdmStationMatches::route('/'),
        ];
    }
}
