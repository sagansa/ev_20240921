<?php

use App\Models\User;
use App\Models\Charge;
use App\Models\Vehicle;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets vehicle charges', function () {
    $vehicle = Vehicle::factory()->create();
    $charges = Charge::factory()
        ->count(2)
        ->create([
            'vehicle_id' => $vehicle->id,
        ]);

    $response = $this->getJson(route('api.vehicles.charges.index', $vehicle));

    $response->assertOk()->assertSee($charges[0]->id);
});

test('it stores the vehicle charges', function () {
    $vehicle = Vehicle::factory()->create();
    $data = Charge::factory()
        ->make([
            'vehicle_id' => $vehicle->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route('api.vehicles.charges.store', $vehicle),
        $data
    );

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);
    unset($data['image_start']);
    unset($data['image_finish']);

    $this->assertDatabaseHas('charges', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $charge = Charge::latest('id')->first();

    $this->assertEquals($vehicle->id, $charge->vehicle_id);
});
