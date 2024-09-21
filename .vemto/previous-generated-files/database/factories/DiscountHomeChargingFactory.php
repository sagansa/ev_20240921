<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\DiscountHomeCharging;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountHomeChargingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DiscountHomeCharging::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'month' => fake()->date(),
            'total_kwh' => fake()->randomFloat(2, 0, 9999),
            'discount_kwh' => fake()->word(),
            'discount_total' => fake()->randomFloat(2, 0, 9999),
            'user_id' => \App\Models\User::factory(),
            'charger_location_id' => \App\Models\ChargerLocation::factory(),
        ];
    }
}
