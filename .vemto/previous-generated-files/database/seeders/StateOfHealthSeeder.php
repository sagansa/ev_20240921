<?php

namespace Database\Seeders;

use App\Models\StateOfHealth;
use Illuminate\Database\Seeder;

class StateOfHealthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StateOfHealth::factory()
            ->count(5)
            ->create();
    }
}
