<?php

namespace App\Filament\Resources\Panel;

use App\Models\SalesImport;
use App\Services\GaikindoImportService;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\SalesImportResource\Pages;
use App\Filament\Resources\Panel\SalesImportResource\RelationManagers;

class SalesImportResource extends Resource
{
    protected static ?string $model = SalesImport::class;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Referensi Kendaraan';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getNavigationLabel(): string
    {
        return 'Import Penjualan (GAIKINDO)';
    }

    public static function canCreate(): bool
    {
        return false; // dibuat via aksi "Jalankan Import" (upload xlsx)
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('year')->label('Tahun')->sortable(),

                TextColumn::make('file_name')->label('File')
                    ->searchable()
                    ->tooltip(fn (SalesImport $record) => $record->file_name),

                TextColumn::make('source')->label('Sumber')->badge()->color('gray'),

                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'processed' => 'Lengkap',
                        'partial' => 'Partial',
                        'failed' => 'Gagal',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'processed' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('meta.coverage')->label('Coverage')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : sprintf('%.1f%%', ((float) $state) * 100))
                    ->color(fn ($state) => $state === null ? 'gray' : (((float) $state) >= 0.9 ? 'success' : 'warning')),

                TextColumn::make('meta.official_total')->label('Total Resmi')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state)),

                TextColumn::make('period_start')->label('Periode')
                    ->formatStateUsing(fn (SalesImport $record) => trim(($record->period_start?->format('M Y') ?? '').' – '.($record->period_end?->format('M Y') ?? ''))),

                TextColumn::make('created_at')->label('Diimport')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')->label('Tahun')
                    ->options(fn () => SalesImport::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
                Tables\Filters\SelectFilter::make('status')->label('Status')
                    ->options(['processed' => 'Lengkap', 'partial' => 'Partial', 'failed' => 'Gagal']),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('stats'));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StatsRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Infolists\Components\Section::make('Ringkasan Import')->schema([
                \Filament\Infolists\Components\TextEntry::make('file_name')->label('File'),
                \Filament\Infolists\Components\TextEntry::make('source')->label('Sumber')->badge(),
                \Filament\Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                \Filament\Infolists\Components\TextEntry::make('period')->label('Periode')
                    ->state(fn ($record) => trim(($record->period_start?->format('d M Y') ?? '').' – '.($record->period_end?->format('d M Y') ?? ''))),
                \Filament\Infolists\Components\TextEntry::make('totals')->label('Terparse vs resmi')
                    ->state(fn ($record) => number_format($record->meta['parsed_total'] ?? 0).' / '.number_format($record->meta['official_total'] ?? 0)
                        .(isset($record->meta['coverage']) ? sprintf(' (coverage %.1f%%)', ($record->meta['coverage'] ?? 0) * 100) : '')),
                \Filament\Infolists\Components\TextEntry::make('officials')->label('Angka resmi (DOMESTIC / PASSENGER / COMMERCIAL)')
                    ->state(fn ($record) => collect($record->meta['official'] ?? [])
                        ->map(fn ($o, $k) => $k.': '.number_format($o['total']).' — '.mb_substr($o['label'], 0, 60))
                        ->implode("\n") ?: '—')
                    ->listWithLineBreaks(),
                \Filament\Infolists\Components\TextEntry::make('warnings')->label('Peringatan')
                    ->state(fn ($record) => implode("\n", $record->meta['warnings'] ?? []) ?: '—')
                    ->listWithLineBreaks(),
                \Filament\Infolists\Components\TextEntry::make('matcher')->label('Hasil matcher katalog')
                    ->state(fn ($record) => collect($record->meta['matcher'] ?? [])
                        ->map(fn ($v, $k) => $k.': '.number_format($v))
                        ->implode("\n") ?: '—')
                    ->listWithLineBreaks(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesImports::route('/'),
            'view' => Pages\ViewSalesImport::route('/{record}'),
        ];
    }
}
