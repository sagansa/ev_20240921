<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiscountHomeCharging;

class DiscountHomeChargingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DiscountHomeCharging::factory()
            ->count(5)
            ->create();
    }
}
