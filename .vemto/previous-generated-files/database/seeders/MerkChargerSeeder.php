<?php

namespace Database\Seeders;

use App\Models\MerkCharger;
use Illuminate\Database\Seeder;

class MerkChargerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MerkCharger::factory()
            ->count(5)
            ->create();
    }
}
