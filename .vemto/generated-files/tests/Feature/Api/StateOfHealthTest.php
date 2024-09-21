<?php

use App\Models\User;
use App\Models\Vehicle;
use Laravel\Sanctum\Sanctum;
use App\Models\StateOfHealth;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets state_of_healths list', function () {
    $stateOfHealths = StateOfHealth::factory()
        ->count(5)
        ->create();

    $response = $this->get(route('api.state-of-healths.index'));

    $response->assertOk()->assertSee($stateOfHealths[0]->image);
});

test('it stores the state_of_health', function () {
    $data = StateOfHealth::factory()
        ->make()
        ->toArray();

    $response = $this->postJson(route('api.state-of-healths.store'), $data);

    unset($data['created_at']);
    unset($data['updated_at']);

    $this->assertDatabaseHas('state_of_healths', $data);

    $response->assertStatus(201)->assertJsonFragment($data);
});

test('it updates the state_of_health', function () {
    $stateOfHealth = StateOfHealth::factory()->create();

    $vehicle = Vehicle::factory()->create();
    $user = User::factory()->create();

    $data = [
        'image' => fake()->optional(),
        'km' => fake()->randomNumber(),
        'percentage' => fake()->randomNumber(),
        'remaining_battery' => fake()->randomNumber(),
        'created_at' => fake()->dateTime(),
        'updated_at' => fake()->dateTime(),
        'vehicle_id' => $vehicle->id,
        'user_id' => $user->id,
    ];

    $response = $this->putJson(
        route('api.state-of-healths.update', $stateOfHealth),
        $data
    );

    unset($data['created_at']);
    unset($data['updated_at']);

    $data['id'] = $stateOfHealth->id;

    $this->assertDatabaseHas('state_of_healths', $data);

    $response->assertStatus(200)->assertJsonFragment($data);
});

test('it deletes the state_of_health', function () {
    $stateOfHealth = StateOfHealth::factory()->create();

    $response = $this->deleteJson(
        route('api.state-of-healths.destroy', $stateOfHealth)
    );

    $this->assertModelMissing($stateOfHealth);

    $response->assertNoContent();
});
