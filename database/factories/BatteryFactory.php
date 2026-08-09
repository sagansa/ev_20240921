<?php

namespace Database\Factories;

use App\Models\Battery;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatteryFactory extends Factory
{
    protected $model = Battery::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'user_id' => User::factory(),
            'label' => fake()->optional()->randomElement(['Original', 'Battery A', 'Battery B']),
            'serial_number' => fake()->optional()->bothify('SN-####-####'),
            'capacity_kwh' => fake()->optional()->randomFloat(2, 10, 100),
            'installed_at' => fake()->date(),
            'installed_km' => fake()->optional()->numberBetween(0, 200000),
            'removed_at' => null,
            'removed_km' => null,
            'status' => 1,
            'note' => null,
        ];
    }

    public function retired(): static
    {
        return $this->state(fn () => [
            'removed_at' => fake()->date(),
            'removed_km' => fake()->numberBetween(1000, 200000),
            'status' => 0,
        ]);
    }
}
