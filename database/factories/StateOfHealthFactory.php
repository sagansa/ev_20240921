<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\StateOfHealth;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateOfHealthFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StateOfHealth::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => fake()->optional()->imageUrl(640, 480, 'transport', true, 'soh'),
            'date' => fake()->date(),
            'km' => fake()->numberBetween(1000, 200000),
            'percentage' => fake()->numberBetween(40, 100),
            'remaining_battery' => fake()->numberBetween(10, 90),
            'deleted_at' => null,
            'vehicle_id' => \App\Models\Vehicle::factory(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
