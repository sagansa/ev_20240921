<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\ModelVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelVehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ModelVehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => fake()->word(),
            'name' => fake()->name(),
            'brand_vehicle_id' => \App\Models\BrandVehicle::factory(),
        ];
    }
}
