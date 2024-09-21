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

test('it gets vehicle state_of_healths', function () {
    $vehicle = Vehicle::factory()->create();
    $stateOfHealths = StateOfHealth::factory()
        ->count(2)
        ->create([
            'vehicle_id' => $vehicle->id,
        ]);

    $response = $this->getJson(
        route('api.vehicles.state-of-healths.index', $vehicle)
    );

    $response->assertOk()->assertSee($stateOfHealths[0]->id);
});

test('it stores the vehicle state_of_healths', function () {
    $vehicle = Vehicle::factory()->create();
    $data = StateOfHealth::factory()
        ->make([
            'vehicle_id' => $vehicle->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route('api.vehicles.state-of-healths.store', $vehicle),
        $data
    );

    unset($data['date']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('state_of_healths', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $stateOfHealth = StateOfHealth::latest('id')->first();

    $this->assertEquals($vehicle->id, $stateOfHealth->vehicle_id);
});
