<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChargerLocation;

class ChargerLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ChargerLocation::factory()
            ->count(5)
            ->create();
    }
}
