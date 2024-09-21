<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\TypeVehicle;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets vehicles list', function () {
    $vehicles = Vehicle::factory()
        ->count(5)
        ->create();

    $response = $this->get(route('api.vehicles.index'));

    $response->assertOk()->assertSee($vehicles[0]->id);
});

test('it stores the vehicle', function () {
    $data = Vehicle::factory()
        ->make()
        ->toArray();

    $response = $this->postJson(route('api.vehicles.store'), $data);

    unset($data['image']);
    unset($data['user_id']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('vehicles', $data);

    $response->assertStatus(201)->assertJsonFragment($data);
});

test('it updates the vehicle', function () {
    $vehicle = Vehicle::factory()->create();

    $brandVehicle = BrandVehicle::factory()->create();
    $modelVehicle = ModelVehicle::factory()->create();
    $typeVehicle = TypeVehicle::factory()->create();
    $user = User::factory()->create();

    $data = [
        'image' => fake()->word(),
        'license_plate' => fake()->name(),
        'ownership' => fake()->date(),
        'status' => fake()->numberBetween(1, 2),
        'created_at' => fake()->dateTime(),
        'updated_at' => fake()->dateTime(),
        'deleted_at' => fake()->dateTime(),
        'brand_vehicle_id' => $brandVehicle->id,
        'model_vehicle_id' => $modelVehicle->id,
        'type_vehicle_id' => $typeVehicle->id,
        'user_id' => $user->id,
    ];

    $response = $this->putJson(route('api.vehicles.update', $vehicle), $data);

    unset($data['image']);
    unset($data['user_id']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $data['id'] = $vehicle->id;

    $this->assertDatabaseHas('vehicles', $data);

    $response->assertStatus(200)->assertJsonFragment($data);
});

test('it deletes the vehicle', function () {
    $vehicle = Vehicle::factory()->create();

    $response = $this->deleteJson(route('api.vehicles.destroy', $vehicle));

    $this->assertSoftDeleted($vehicle);

    $response->assertNoContent();
});
