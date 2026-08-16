<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\TesterResource\Pages;
use App\Models\Tester;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Tester funnel (Closed Testing) — pantau siapa yang register via build IAS +
 * siapa yang aktif di build testing (ping channel `store`). Header action
 * "Export CSV" menghasilkan email list utk undangan Play Console.
 */
class TesterResource extends Resource
{
    protected static ?string $model = Tester::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Testers';

    protected static ?string $modelLabel = 'Tester';

    protected static ?string $pluralModelLabel = 'Testers';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'store_active' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state): string => $state === 'store_active'
                        ? 'Aktif di build testing'
                        : 'Terdaftar'),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('last_ping_at')
                    ->label('Last Ping')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_ping_version_code')
                    ->label('Versi Terakhir')
                    ->placeholder('—')
                    ->alignCenter(),
                TextColumn::make('active_days')
                    ->label('Hari Aktif')
                    ->getStateUsing(fn (Tester $record): int => $record->active_days)
                    ->alignCenter()
                    ->color(fn (Tester $record): string => $record->active_days >= 14 ? 'success' : 'gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTesters::route('/'),
        ];
    }
}
