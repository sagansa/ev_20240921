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
     *
     * Sumber riset (Sep 2026): situs resmi grup + Wikipedia — termasuk
     * detail Volvo (VOLVO katalog = Volvo CARS → Geely; sedangkan UD
     * Trucks = truk yang dijual Volvo Group ke Isuzu Motors pada 2021),
     * Scania via TRATON SE (mayoritas milik Volkswagen Group), dan
     * Seres Group (eks Sokon) yang memayungi DFSK & SERES.
     */
    public const GROUPS = [
        // Eropa
        'Volkswagen Group' => ['Volkswagen', 'Audi', 'Porsche', 'Scania'],
        'Stellantis' => ['Peugeot', 'Citroen', 'Jeep', 'DS'],
        'Renault-Nissan-Mitsubishi' => ['Nissan', 'Mitsubishi', 'Renault', 'Datsun'],
        'BMW Group' => ['BMW', 'MINI'],
        // Jepang
        'Toyota Group' => ['Toyota', 'Lexus', 'Daihatsu', 'Hino'],
        'Isuzu' => ['Isuzu', 'UD Trucks'],
        // Tiongkok
        'Geely' => ['Geely', 'Zeekr', 'Volvo', 'Farizon', 'Polestar', 'Proton', 'Lotus'],
        'Chery Group' => ['Chery', 'Omoda', 'Jaecoo', 'Jetour'],
        'Changan Group' => ['Changan', 'Deepal'],
        'Seres Group' => ['DFSK', 'Seres'],
        'GAC' => ['GAC', 'Aion'],
        'SAIC' => ['MG', 'Wuling', 'Maxus'],
        'BYD Group' => ['BYD', 'Denza'],
        'Hyundai Motor Group' => ['Hyundai', 'Kia', 'Genesis'],
        'Hozon Auto' => ['Neta'],
        'Vingroup' => ['VinFast'],
        'Tata Group' => ['Tata'],
        'FAW Group' => ['FAW'],
        'BAIC Group' => ['BAIC'],
        // Brand lain sengaja mandiri (induknya identik dgn brandnya sendiri
        // atau tidak punya saudara di katalog): Honda, Suzuki, Mazda, Subaru,
        // Ford, Mercedes Benz, XPeng, GWM, Polytron, Aletra.
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
