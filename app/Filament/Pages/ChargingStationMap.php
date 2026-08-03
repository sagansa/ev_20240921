<?php

namespace App\Filament\Pages;

use App\Models\ChargingStation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman peta Filament: tampilkan seluruh titik charging_stations di OSM.
 * Hanya super_admin yg bisa akses. Data di-fetch via AJAX (bukan inline)
 * utk menghindari 500KB+ JSON di HTML yang bikin Livewire/browser berat.
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

    /**
     * Endpoint AJAX: return stations sebagai GeoJSON-lite (lat, lng, level, nama).
     * Dipanggil oleh fetch() di Blade view.
     */
    public function fetchStations(): array
    {
        return ChargingStation::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select(['id', 'nama_lokasi', 'latitude', 'longitude', 'availability_level', 'available_count', 'total_konektor', 'type_charge', 'provinsi'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'nama' => $s->nama_lokasi,
                'lat' => (float) $s->latitude,
                'lng' => (float) $s->longitude,
                'level' => $s->availability_level ?? 'unknown',
                'avail' => $s->available_count,
                'total' => $s->total_konektor,
                'type' => $s->type_charge ?? '—',
                'provinsi' => $s->provinsi ?? '—',
            ])
            ->values()
            ->toArray();
    }

    protected function getViewData(): array
    {
        return [
            'totalCount' => ChargingStation::whereNotNull('latitude')->whereNotNull('longitude')->count(),
        ];
    }
}
