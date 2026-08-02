<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\SpkluScrapeRawResource\Pages;
use App\Models\Provider;
use App\Models\SpkluScrapeRaw;
use App\Services\ScrapeDedupService;
use App\Services\SpkluScrapeMergeService;
use App\Tables\Columns\ScrapeStatusColumn;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class SpkluScrapeRawResource extends Resource
{
    protected static ?string $model = SpkluScrapeRaw::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Provider & SPKLU';

    protected static ?string $navigationLabel = 'Review Hasil Scrape';

    protected static ?string $modelLabel = 'Scrape SPKLU';

    protected static ?string $pluralModelLabel = 'Review Hasil Scrape';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Data Lokasi')->schema([
                Grid::make(2)->schema([
                    TextInput::make('nama_lokasi')->required()->string(),
                    TextInput::make('place_id')->nullable()->string(),
                    TextInput::make('alamat')->nullable()->string()->columnSpanFull(),
                    TextInput::make('latitude')->nullable()->numeric(),
                    TextInput::make('longitude')->nullable()->numeric(),
                    TextInput::make('rating')->nullable()->numeric(),
                    TextInput::make('total_reviews')->nullable()->numeric(),
                    TextInput::make('phone')->nullable()->string(),
                    TextInput::make('opening_hours')->nullable()->string(),
                    TextInput::make('website')->nullable()->string(),
                ]),
            ]),

            Section::make('Data Charger')->schema([
                Grid::make(3)->schema([
                    TextInput::make('provider_name')->nullable()->string(),
                    TextInput::make('type_charge')->nullable()->string(),
                    TextInput::make('max_kw')->nullable()->numeric(),
                    TextInput::make('total_charger')->nullable()->numeric(),
                    TextInput::make('total_konektor')->nullable()->numeric(),
                ]),
            ]),

            Section::make('Metadata')->schema([
                Grid::make(3)->schema([
                    TextInput::make('dedup_hash')->nullable()->string(),
                    TextInput::make('scrape_session')->nullable()->string(),
                    Select::make('status')
                        ->options([
                            0 => 'new',
                            1 => 'duplicate',
                            2 => 'approved',
                            3 => 'rejected',
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('nama_lokasi')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('provider_name')
                    ->label('Provider (tebakan)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chargers.connector_type')
                    ->label('Konektor')
                    ->badge()
                    ->limitList(3)
                    ->separator(',')
                    ->color('gray'),
                TextColumn::make('type_charge')
                    ->label('Tier')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ultrafast' => 'danger',
                        'fast' => 'warning',
                        'medium' => 'info',
                        'standard' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('max_kw')
                    ->label('Max kW')
                    ->alignCenter(),
                TextColumn::make('total_charger')
                    ->label('Charger')
                    ->alignCenter(),
                TextColumn::make('scrape_session')
                    ->label('Session')
                    ->searchable()
                    ->limit(10),
                TextColumn::make('linked_spklu_location_id')
                    ->label('Link Produksi (referensi)')
                    ->getStateUsing(fn (SpkluScrapeRaw $record): string => $record->linkedLocation
                        ? "→ {$record->linkedLocation->nama_lokasi} (#{$record->linkedLocation->id})"
                        : '—')
                    ->placeholder('—')
                    ->limit(40)
                    ->color(fn (SpkluScrapeRaw $record): string => $record->linked_spklu_location_id ? 'info' : 'gray'),
                ScrapeStatusColumn::make('status')
                    ->label('Status'),
                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        0 => 'new',
                        2 => 'approved (tampil di map)',
                        3 => 'rejected',
                    ]),
                SelectFilter::make('scrape_session')
                    ->label('Filter Session')
                    ->options(fn () => SpkluScrapeRaw::distinct()->pluck('scrape_session', 'scrape_session')->toArray())
                    ->searchable(),
                SelectFilter::make('provider_name')
                    ->label('Filter Provider')
                    ->options(fn () => SpkluScrapeRaw::query()->whereNotNull('provider_name')->distinct()->pluck('provider_name', 'provider_name')->toArray())
                    ->searchable(),
                TernaryFilter::make('tanpa_provider')
                    ->label('Tanpa Provider')
                    ->placeholder('Semua')
                    ->trueLabel('Tanpa provider')
                    ->falseLabel('Punya provider')
                    ->queries(
                        true: fn ($query) => $query->whereNull('guessed_provider_id'),
                        false: fn ($query) => $query->whereNotNull('guessed_provider_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\Action::make('approve')
                        ->label('Tandai Tampil di Map')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->form(static::getApproveFormSchema())
                        ->fillForm(fn (SpkluScrapeRaw $record): array => static::getApproveFormDefaults($record))
                        ->action(function (SpkluScrapeRaw $record, array $data): void {
                            app(SpkluScrapeMergeService::class)->markApproved(
                                $record,
                                isset($data['linked_spklu_location_id']) && $data['linked_spklu_location_id']
                                    ? (int) $data['linked_spklu_location_id']
                                    : null,
                            );

                            Notification::make()
                                ->title('Ditandai tampil di map')
                                ->body('Data ini akan muncul di peta via layer scrape (tanpa mengubah data JSON produksi).')
                                ->success()
                                ->send();
                        }),
                    Actions\Action::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (SpkluScrapeRaw $record): void {
                            app(SpkluScrapeMergeService::class)->reject($record);

                            Notification::make()
                                ->title('Data ditolak')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('bulkApprove')
                        ->label('Tandai Tampil Terpilih')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(SpkluScrapeMergeService::class);
                            foreach ($records as $record) {
                                $service->markApproved($record);
                            }

                            Notification::make()
                                ->title(count($records).' data ditandai tampil di map')
                                ->success()
                                ->send();
                        }),
                    Actions\BulkAction::make('bulkReject')
                        ->label('Tolak Terpilih')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(SpkluScrapeMergeService::class);
                            foreach ($records as $record) {
                                $service->reject($record);
                            }

                            Notification::make()
                                ->title(count($records).' data ditolak')
                                ->success()
                                ->send();
                        }),
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getApproveFormSchema(): array
    {
        return [
            Placeholder::make('display_context')
                ->label('Cara kerja')
                ->content('Data ini hanya di-mark "tampil". Tidak menambah/mengubah data produksi JSON. Yang muncul di map berasal dari UNION layer scrape saat query API.')
                ->columnSpanFull(),
            Placeholder::make('recommendations')
                ->label('Rekomendasi kandidat terdekat dari data JSON')
                ->content(function (?SpkluScrapeRaw $record): \Illuminate\Support\HtmlString {
                    if (! $record) {
                        return new \Illuminate\Support\HtmlString('—');
                    }
                    $candidates = app(ScrapeDedupService::class)->recommendCandidates($record, 5);
                    if (empty($candidates)) {
                        return new \Illuminate\Support\HtmlString('<em>Tidak ada kandidat — tempat ini kemungkinan baru.</em>');
                    }
                    $items = array_map(fn ($c) => "<li><b>{$c['similarity_pct']}%</b> mirip, <b>{$c['distance_km']} km</b> ({$c['reason']}) — #{$c['id']} ".e($c['nama_lokasi']).'</li>', $candidates);

                    return new \Illuminate\Support\HtmlString('<ul style="margin:.3em 0">'.implode('', $items).'</ul>');
                })
                ->columnSpanFull(),
            Select::make('linked_spklu_location_id')
                ->label('Link ke produksi (opsional, hanya referensi)')
                ->options(function (?SpkluScrapeRaw $record) {
                    if (! $record) {
                        return [];
                    }
                    $cands = app(ScrapeDedupService::class)->recommendCandidates($record, 10);

                    return array_column($cands, 'nama_lokasi', 'id');
                })
                ->searchable()
                ->placeholder('Tidak dilink (tempat baru)')
                ->helperText('Pilih kalau ini tempat yang sama dengan data JSON. Tidak mengubah data JSON.'),
            Grid::make(2)->schema([
                TextInput::make('nama_lokasi')
                    ->label('Nama Lokasi')
                    ->required()
                    ->string(),
                Select::make('guessed_provider_id')
                    ->label('Provider EV')
                    ->options(fn () => Provider::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->placeholder('Pilih provider'),
                TextInput::make('type_charge')
                    ->label('Type Charge (speed tier)')
                    ->string(),
                TextInput::make('watt')
                    ->label('Watt')
                    ->string(),
            ]),
        ];
    }

    public static function getApproveFormDefaults(SpkluScrapeRaw $record): array
    {
        return [
            'nama_lokasi' => $record->nama_lokasi,
            'guessed_provider_id' => $record->guessed_provider_id,
            'linked_spklu_location_id' => $record->linked_spklu_location_id,
            'type_charge' => $record->type_charge,
            'watt' => $record->max_kw !== null ? $record->max_kw.' kW' : null,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpkluScrapeRaws::route('/'),
        ];
    }
}
