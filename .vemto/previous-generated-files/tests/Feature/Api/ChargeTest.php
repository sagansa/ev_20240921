<?php

use App\Models\User;
use App\Models\Charge;
use App\Models\Vehicle;
use App\Models\Charger;
use Laravel\Sanctum\Sanctum;
use App\Models\ChargerLocation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets charges list', function () {
    $charges = Charge::factory()
        ->count(5)
        ->create();

    $response = $this->get(route('api.charges.index'));

    $response->assertOk()->assertSee($charges[0]->id);
});

test('it stores the charge', function () {
    $data = Charge::factory()
        ->make()
        ->toArray();

    $response = $this->postJson(route('api.charges.store'), $data);

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);
    unset($data['image_start']);
    unset($data['image_finish']);

    $this->assertDatabaseHas('charges', $data);

    $response->assertStatus(201)->assertJsonFragment($data);
});

test('it updates the charge', function () {
    $charge = Charge::factory()->create();

    $vehicle = Vehicle::factory()->create();
    $chargerLocation = ChargerLocation::factory()->create();
    $user = User::factory()->create();
    $charger = Charger::factory()->create();

    $data = [
        'date' => fake()->date(),
        'km_now' => fake()->randomNumber(),
        'km_before' => fake()->randomNumber(),
        'start_charging_now' => fake()->randomNumber(),
        'finish_charging_now' => fake()->randomNumber(),
        'finish_charging_before' => fake()->randomNumber(),
        'parking' => fake()->randomNumber(),
        'kWh' => fake()->randomFloat(0, 9999),
        'street_lighting_tax' => fake()->randomNumber(),
        'value_added_tax' => fake()->randomNumber(),
        'admin_cost' => fake()->randomNumber(),
        'total_cost' => fake()->randomNumber(),
        'image_start' => fake()->text(255),
        'image_finish' => fake()->word(),
        'vehicle_id' => $vehicle->id,
        'charger_location_id' => $chargerLocation->id,
        'user_id' => $user->id,
        'charger_id' => $charger->id,
    ];

    $response = $this->putJson(route('api.charges.update', $charge), $data);

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);
    unset($data['image_start']);
    unset($data['image_finish']);

    $data['id'] = $charge->id;

    $this->assertDatabaseHas('charges', $data);

    $response->assertStatus(200)->assertJsonFragment($data);
});

test('it deletes the charge', function () {
    $charge = Charge::factory()->create();

    $response = $this->deleteJson(route('api.charges.destroy', $charge));

    $this->assertSoftDeleted($charge);

    $response->assertNoContent();
});
