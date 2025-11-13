<?php

namespace Database\Factories;

use App\Models\Charger;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Charger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit' => fake()->numberBetween(1, 2),
            'deleted_at' => null,
            'current_charger_id' => \App\Models\CurrentCharger::factory(),
            'type_charger_id' => \App\Models\TypeCharger::factory(),
            'power_charger_id' => \App\Models\PowerCharger::factory(),
            'charger_location_id' => \App\Models\ChargerLocation::factory(),
            'merk_charger_id' => \App\Models\MerkCharger::factory(),
        ];
    }
}
