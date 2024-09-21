<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\CurrentCharger;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrentChargerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CurrentCharger::class;

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
