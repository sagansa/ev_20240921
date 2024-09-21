<?php

namespace Database\Factories;

use App\Models\TypeVehicle;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeVehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TypeVehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'battery_capacity' => fake()->numberBetween(1, 2),
            'type_charger' => fake()->word(),
            'model_vehicle_id' => \App\Models\ModelVehicle::factory(),
        ];
    }
}
