<?php

namespace Database\Factories;

use App\Models\Charge;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Charge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'km_now' => fake()->randomNumber(),
            'km_before' => fake()->randomNumber(),
            'start_charging_now' => fake()->randomNumber(),
            'finish_charging_now' => fake()->randomNumber(),
            'finish_charging_before' => fake()->randomNumber(),
            'parking' => fake()->randomNumber(),
            'kWh' => fake()->randomFloat(0, 9999),
            'street_lighting_tax' => fake()->randomNumber(),
            'value_added_tax' => fake()->randomNumber(),
            'admin_cost' => fake()->randomNumber(),
            'total_cost' => fake()->randomNumber(),
            'image_start' => fake()->text(255),
            'image_finish' => fake()->word(),
            'deleted_at' => fake()->dateTime(),
            'vehicle_id' => \App\Models\Vehicle::factory(),
            'charger_location_id' => \App\Models\ChargerLocation::factory(),
            'user_id' => \App\Models\User::factory(),
            'charger_id' => \App\Models\Charger::factory(),
        ];
    }
}
