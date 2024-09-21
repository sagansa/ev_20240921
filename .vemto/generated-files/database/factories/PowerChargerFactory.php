<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\PowerCharger;
use Illuminate\Database\Eloquent\Factories\Factory;

class PowerChargerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PowerCharger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type_charger_id' => \App\Models\TypeCharger::factory(),
        ];
    }
}
