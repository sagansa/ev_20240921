<?php

namespace Database\Seeders;

use App\Models\CurrentCharger;
use Illuminate\Database\Seeder;

class CurrentChargerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CurrentCharger::factory()
            ->count(5)
            ->create();
    }
}
