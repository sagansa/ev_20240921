<?php

namespace Database\Factories;

use App\Models\PostalCode;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostalCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PostalCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'province_id' => \App\Models\Province::factory(),
            'city_id' => \App\Models\City::factory(),
            'district_id' => \App\Models\District::factory(),
            'subdistrict_id' => \App\Models\Subdistrict::factory(),
        ];
    }
}
