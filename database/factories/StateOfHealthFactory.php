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
            'image' => fake()->optional(),
            'date' => fake()->date(),
            'km' => fake()->randomNumber(),
            'percentage' => fake()->randomNumber(),
            'remaining_battery' => fake()->randomNumber(),
            'deleted_at' => fake()->dateTime(),
            'vehicle_id' => \App\Models\Vehicle::factory(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
