<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => fake()->word(),
            'license_plate' => fake()->name(),
            'ownership' => fake()->date(),
            'status' => fake()->numberBetween(1, 2),
            'deleted_at' => null,
            'brand_vehicle_id' => \App\Models\BrandVehicle::factory(),
            'model_vehicle_id' => \App\Models\ModelVehicle::factory(),
            'type_vehicle_id' => \App\Models\TypeVehicle::factory(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
