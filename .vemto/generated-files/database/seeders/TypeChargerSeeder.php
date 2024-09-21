<?php

namespace Database\Seeders;

use App\Models\TypeCharger;
use Illuminate\Database\Seeder;

class TypeChargerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeCharger::factory()
            ->count(5)
            ->create();
    }
}
