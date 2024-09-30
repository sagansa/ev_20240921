<?php

namespace Database\Factories;

use App\Models\MerkCharger;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerkChargerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MerkCharger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
