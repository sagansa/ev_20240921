<?php

namespace Database\Seeders;

use App\Models\Charger;
use Illuminate\Database\Seeder;

class ChargerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Charger::factory()
            ->count(5)
            ->create();
    }
}
