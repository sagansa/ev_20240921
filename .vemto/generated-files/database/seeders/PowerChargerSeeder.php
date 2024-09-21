<?php

namespace Database\Seeders;

use App\Models\PowerCharger;
use Illuminate\Database\Seeder;

class PowerChargerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PowerCharger::factory()
            ->count(5)
            ->create();
    }
}
