<?php

namespace Database\Factories;

use App\Models\TypeCharger;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeChargerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TypeCharger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'current_charger_id' => \App\Models\CurrentCharger::factory(),
        ];
    }
}
