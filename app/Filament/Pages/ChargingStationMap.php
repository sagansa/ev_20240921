<?php

namespace App\Filament\Pages;

use App\Models\ChargingStation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman peta Filament: tampilkan seluruh titik charging_stations di OSM.
 * Hanya super_admin yg bisa akses.
 *
 * Catatan: Leaflet JS/CSS + init dirender via Filament render hook
 * (PanelsRenderHook::STYLES_AFTER / SCRIPTS_AFTER, lihat AdminPanelProvider)
 * karena Livewire wire:navigate (SPA) strip <script> inline dari output
 * komponen — render hook menempel di layout sehingga bertahan saat navigasi.
 */
class ChargingStationMap extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Provider & SPKLU';

    protected static ?string $navigationLabel = 'Peta Stasiun Charging';

    protected static ?string $title = 'Peta Stasiun Charging (Publik)';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.charging-station-map';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'totalCount' => ChargingStation::whereNotNull('latitude')->whereNotNull('longitude')->count(),
        ];
    }
}
