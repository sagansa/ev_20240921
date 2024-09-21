<?php

use App\Models\User;
use App\Models\Charge;
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

test('it gets charger_location charges', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $charges = Charge::factory()
        ->count(2)
        ->create([
            'charger_location_id' => $chargerLocation->id,
        ]);

    $response = $this->getJson(
        route('api.charger-locations.charges.index', $chargerLocation)
    );

    $response->assertOk()->assertSee($charges[0]->id);
});

test('it stores the charger_location charges', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $data = Charge::factory()
        ->make([
            'charger_location_id' => $chargerLocation->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route('api.charger-locations.charges.store', $chargerLocation),
        $data
    );

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('charges', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $charge = Charge::latest('id')->first();

    $this->assertEquals($chargerLocation->id, $charge->charger_location_id);
});
