<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Provider::class;

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
            'status' => fake()->numberBetween(1, 2),
            'contact' => fake()->randomNumber(),
            'address' => fake()->address(),
            'province_id' => \App\Models\Province::factory(),
            'city_id' => \App\Models\City::factory(),
            'district_id' => \App\Models\District::factory(),
            'subdistrict_id' => \App\Models\Subdistrict::factory(),
            'postal_code_id' => \App\Models\PostalCode::factory(),
        ];
    }
}
