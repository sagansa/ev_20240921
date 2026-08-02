<?php

namespace App\Filament\Concerns;

/**
 * Warna badge status availability lintas resource ESDM + canonical.
 *
 * Level diproduksi oleh EsdmSinggatStatusPollerService / CanonicalStationHydrateService:
 * available, partial, occupied, offline, unknown.
 */
trait AvailabilityLevelColors
{
    public static function availabilityLevelColor(string $level): string
    {
        return match ($level) {
            'available' => 'success',
            'partial' => 'warning',
            'occupied' => 'danger',
            'offline' => 'gray',
            default => 'gray',
        };
    }

    public static function availabilityLevelLabel(string $level): string
    {
        return match ($level) {
            'available' => 'Tersedia',
            'partial' => 'Sebagian',
            'occupied' => 'Penuh',
            'offline' => 'Offline',
            default => 'Tidak Diketahui',
        };
    }
}
