<?php

namespace Database\Factories;

use App\Models\Subdistrict;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubdistrictFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subdistrict::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'district_id' => \App\Models\District::factory(),
        ];
    }
}
