<?php

namespace Database\Seeders;

use App\Models\BrandVehicle;
use Illuminate\Database\Seeder;

class BrandVehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BrandVehicle::factory()
            ->count(5)
            ->create();
    }
}
