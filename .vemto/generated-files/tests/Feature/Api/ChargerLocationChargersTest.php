<?php

use App\Models\User;
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

test('it gets charger_location chargers', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $chargers = Charger::factory()
        ->count(2)
        ->create([
            'charger_location_id' => $chargerLocation->id,
        ]);

    $response = $this->getJson(
        route('api.charger-locations.chargers.index', $chargerLocation)
    );

    $response->assertOk()->assertSee($chargers[0]->id);
});

test('it stores the charger_location chargers', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $data = Charger::factory()
        ->make([
            'charger_location_id' => $chargerLocation->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route('api.charger-locations.chargers.store', $chargerLocation),
        $data
    );

    unset($data['merk_charger_id']);
    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('chargers', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $charger = Charger::latest('id')->first();

    $this->assertEquals($chargerLocation->id, $charger->charger_location_id);
});
