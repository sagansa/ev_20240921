<?php

namespace Database\Seeders;

use App\Models\FuelPrice;
use Illuminate\Database\Seeder;

/**
 * Riwayat harga Pertamax (RON 92) sebagai titik awal. Admin dapat mengubah
 * harga kapan pun via Filament; sesi charging lama memakai harga yang berlaku
 * pada tanggal sesi (harga terakhir dengan effective_date <= tanggal sesi).
 */
class FuelPriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            ['effective_date' => '2022-09-01', 'price_per_liter' => 14500],
            ['effective_date' => '2023-10-01', 'price_per_liter' => 12400],
            ['effective_date' => '2025-01-01', 'price_per_liter' => 12100],
            ['effective_date' => '2025-03-15', 'price_per_liter' => 12500],
            ['effective_date' => '2025-06-01', 'price_per_liter' => 12900],
        ];

        foreach ($prices as $price) {
            FuelPrice::updateOrCreate(
                ['effective_date' => $price['effective_date']],
                $price
            );
        }
    }
}
