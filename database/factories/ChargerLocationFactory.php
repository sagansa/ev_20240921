<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\ChargerLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargerLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ChargerLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => fake()->optional(),
            'name' => fake()->name(),
            'location_on' => fake()->numberBetween(1, 2),
            'status' => fake()->numberBetween(1, 2),
            'description' => fake()->sentence(15),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'parking' => fake()->boolean(),
            'address' => fake()->address(),
            'deleted_at' => fake()->dateTime(),
            'provider_id' => \App\Models\Provider::factory(),
            'province_id' => \App\Models\Province::factory(),
            'city_id' => \App\Models\City::factory(),
            'district_id' => \App\Models\District::factory(),
            'subdistrict_id' => \App\Models\Subdistrict::factory(),
            'postal_code_id' => \App\Models\PostalCode::factory(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
