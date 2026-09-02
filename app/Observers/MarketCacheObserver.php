<?php

namespace App\Observers;

use App\Services\VehicleMarketService;

/**
 * Dimensi grup/brand masuk ke payload Pasar EV (leaderboard grup, badge
 * katalog) yang di-cache 24 jam — setiap perubahan admin diwajibkan
 * menaikkan versi cache agar aplikasi langsung melihat klaster baru.
 */
class MarketCacheObserver
{
    public function saved(): void
    {
        app(VehicleMarketService::class)->flush();
    }

    public function deleted(): void
    {
        app(VehicleMarketService::class)->flush();
    }
}
