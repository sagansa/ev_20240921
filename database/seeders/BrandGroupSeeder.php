<?php

namespace Database\Seeders;

use App\Models\BrandGroup;
use App\Models\BrandVehicle;
use App\Services\VehicleMarketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Seed klaster induk perusahaan (grup industri) + link ke brand katalog.
 * Idempoten: grup dibuat via updateOrCreate; link brand di-upsert per grup
 * (brand pindah grup otomatis terbetulkan saat re-seed). Brand yang tidak
 * ada di katalog di-skip + log — seeder tidak pernah membuat brand baru.
 */
class BrandGroupSeeder extends Seeder
{
    /**
     * Mapping grup industri → brand anggota (nama bebas huruf besar/kecil;
     * dicocokkan ke brand_vehicles secara case-insensitive + trim).
     */
    public const GROUPS = [
        'SAIC' => ['MG', 'Wuling', 'Maxus'],
        'BYD Group' => ['BYD', 'Denza'],
        'Toyota Group' => ['Toyota', 'Lexus', 'Daihatsu', 'Hino'],
        'Hyundai Motor Group' => ['Hyundai', 'Kia', 'Genesis'],
        'Stellantis' => ['Peugeot', 'Citroen', 'Jeep', 'DS'],
        'Renault-Nissan-Mitsubishi' => ['Nissan', 'Mitsubishi', 'Renault', 'Datsun'],
        'GAC' => ['GAC', 'AION'],
        'Chery Group' => ['Chery', 'Omoda', 'Jaecoo', 'Jetour'],
        'Geely' => ['Geely', 'Zeekr'],
        'Volkswagen Group' => ['Volkswagen', 'Audi', 'Porsche'],
        'BMW Group' => ['BMW', 'MINI'],
    ];

    public function run(): void
    {
        foreach (self::GROUPS as $groupName => $brandNames) {
            $group = BrandGroup::updateOrCreate(['name' => $groupName]);

            foreach ($brandNames as $brandName) {
                $linked = BrandVehicle::whereRaw('LOWER(TRIM(name)) = ?', [
                    mb_strtolower(trim($brandName)),
                ])->update(['brand_group_id' => $group->id]);

                if ($linked === 0) {
                    // Brand belum ada di katalog — wajar (katalog bertumbuh);
                    // jangan create, cukup jejak agar mudah diaudit.
                    Log::warning("BrandGroupSeeder: brand '{$brandName}' tidak ditemukan di brand_vehicles (grup {$groupName}) — dilewati.");
                    $this->command?->warn("  skip: {$brandName} (tidak ada di katalog, grup {$groupName})");
                }
            }
        }

        // Angka leaderboard grup/katalog memuat dimensi grup — segarkan cache.
        app(VehicleMarketService::class)->flush();
    }
}
