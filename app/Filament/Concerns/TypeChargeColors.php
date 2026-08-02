<?php

namespace App\Filament\Concerns;

/**
 * Warna badge tier type_charge (verbatim ESDM: "Ultra Fast Charging" dll).
 */
trait TypeChargeColors
{
    public static function typeChargeColor(string $type): string
    {
        return match ($type) {
            'Ultra Fast Charging' => 'danger',
            'Fast Charging' => 'warning',
            'Medium Charging' => 'info',
            'Slow Charging' => 'gray',
            default => 'gray',
        };
    }
}
