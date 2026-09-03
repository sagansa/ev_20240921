<?php

namespace Database\Seeders;

use App\Models\BrandVehicle;
use App\Services\VehicleMarketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Seed logo awal merek kendaraan yang sudah tersedia.
 * Idempoten: hanya mengupdate kolom image jika belum diatur atau menimpa dengan file resmi.
 */
class BrandLogoSeeder extends Seeder
{
    public const LOGOS = [
        'BYD' => 'images/brand/byd.png',
        'AION' => 'images/brand/aion.png',
        'NETA' => 'images/brand/neta.jpeg',
        'ALETRA' => 'images/brand/aletra.webp',
    ];

    public function run(): void
    {
        foreach (self::LOGOS as $brandName => $imagePath) {
            $affected = BrandVehicle::whereRaw('LOWER(TRIM(name)) = ?', [
                mb_strtolower(trim($brandName)),
            ])->update(['image' => $imagePath]);

            if ($affected === 0) {
                Log::warning("BrandLogoSeeder: Brand '{$brandName}' tidak ditemukan di katalog brand_vehicles.");
                $this->command?->warn("  skip: {$brandName} (tidak ditemukan di brand_vehicles)");
            } else {
                $this->command?->info("  linked: {$brandName} -> {$imagePath}");
            }
        }

        app(VehicleMarketService::class)->flush();
    }
}
