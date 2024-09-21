<?php

namespace Database\Seeders;

use App\Models\ModelVehicle;
use Illuminate\Database\Seeder;

class ModelVehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModelVehicle::factory()
            ->count(5)
            ->create();
    }
}
