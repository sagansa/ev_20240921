<?php

namespace App\Filament\Widgets;

use App\Models\Tester;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan tester di header resource — bantu pantau syarat kelulusan
 * Closed Testing: ≥12 tester × 14 hari aktif untuk akses production.
 */
class TesterOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Tester::count();
        $active = Tester::where('status', 'store_active')->count();
        $today = Tester::where('status', 'store_active')
            ->whereDate('last_ping_at', today())
            ->count();

        return [
            Stat::make('Total Terdaftar', $total)
                ->description('Syarat: ≥12')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            Stat::make('Aktif di Build Testing', $active)
                ->description('Ping dari channel store')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($active >= 12 ? 'success' : 'warning'),
            Stat::make('Aktif Hari Ini', $today)
                ->description('Ping build store terbaru hari ini')
                ->descriptionIcon('heroicon-m-fire')
                ->color('gray'),
        ];
    }
}
