<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Jetstream\Features;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()
                ->unique()
                ->safeEmail(),
            'email_verified_at' => now(),
            'password' => \Hash::make('password'),
            'two_factor_secret' => fake()->text(),
            'two_factor_recovery_codes' => fake()->text(),
            'remember_token' => \Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Provide compatibility with Jetstream's personal team expectation.
     */
    public function withPersonalTeam(): Factory
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state(fn (array $attributes) => $attributes);
        }

        return $this;
    }
}
