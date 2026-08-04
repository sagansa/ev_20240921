<?php

namespace App\Filament\Resources\Panel\PlnEsdmStationMatchResource\RelationManagers;

use App\Models\PlnEsdmStationMatch;
use App\Services\PlnEsdmMatchService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Daftar kandidat ESDM utk satu PLN (owner). Admin pilih satu sebagai pemenang.
 *
 * Approve satu kandidat → PlnEsdmMatchService::approve() otomatis reject
 * kandidat lain (pending/ai_suggested/approved) utk PLN yg sama ("Superseded").
 */
class CandidatesRelationManager extends RelationManager
{
    protected static string $relationship = 'plnEsdmMatches';

    protected static ?string $title = 'Kandidat ESDM';

    protected static ?string $recordTitleAttribute = 'esdm_name';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('distance_m')
            ->columns([
                TextColumn::make('esdm_name')
                    ->label('Kandidat ESDM')
                    ->searchable()
                    ->weight('bold')
                    ->limit(45),
                TextColumn::make('distance_m')
                    ->label('Jarak')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? number_format($state, 0, ',', '.').' m' : '—')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('similarity_pct')
                    ->label('Nama')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? $state.'%' : '—')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 85 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('ai_confidence')
                    ->label('AI')
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
                        'rejected_ai' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->heading('Kandidat ESDM — pilih satu sebagai pemenang')
            ->description(fn (RelationManager $livewire): string => null)
            ->recordActions([
                Actions\Action::make('approve')
                    ->label('Jadikan Pemenang')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Pilih sebagai pemenang?')
                    ->modalDescription('Kandidat lain untuk PLN ini akan otomatis ditolak (Superseded). Status PLN akan mengikuti status real-time ESDM pemenang.')
                    ->form(\App\Filament\Resources\Panel\PlnEsdmStationMatchResource::comparisonSchema())
                    ->modalSubmitActionLabel('Ya, jadikan pemenang')
                    ->visible(fn (PlnEsdmStationMatch $record): bool => $record->match_status !== PlnEsdmMatchService::STATUS_APPROVED)
                    ->action(function (PlnEsdmStationMatch $record): void {
                        app(PlnEsdmMatchService::class)->approve($record->id, Auth::user()?->email);

                        Notification::make()
                            ->title('Pemenang dipilih')
                            ->body('Status stasiun PLN kini mengikuti ESDM. Kandidat lain ditolak otomatis.')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PlnEsdmStationMatch $record): bool => ! in_array($record->match_status, [PlnEsdmMatchService::STATUS_REJECTED, PlnEsdmMatchService::STATUS_REJECTED_AI], true))
                    ->modalHeading('Tolak kandidat ini?')
                    ->modalSubmitActionLabel('Tolak')
                    ->form([
                        ...\App\Filament\Resources\Panel\PlnEsdmStationMatchResource::comparisonSchema(),
                        Textarea::make('rejected_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (PlnEsdmStationMatch $record, array $data): void {
                        app(PlnEsdmMatchService::class)->reject(
                            $record->id,
                            $data['rejected_reason'] ?? null,
                            Auth::user()?->email,
                        );

                        Notification::make()
                            ->title('Kandidat ditolak')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(false);
    }
}
