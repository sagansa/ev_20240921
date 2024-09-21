<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\BrandVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandVehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BrandVehicle::class;

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
        ];
    }
}
