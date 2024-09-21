<?php

namespace Database\Seeders;

use App\Models\TypeVehicle;
use Illuminate\Database\Seeder;

class TypeVehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeVehicle::factory()
            ->count(5)
            ->create();
    }
}
