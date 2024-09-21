<?php

use App\Models\User;
use App\Models\Charger;
use App\Models\TypeCharger;
use App\Models\PowerCharger;
use Laravel\Sanctum\Sanctum;
use App\Models\CurrentCharger;
use App\Models\ChargerLocation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets chargers list', function () {
    $chargers = Charger::factory()
        ->count(5)
        ->create();

    $response = $this->get(route('api.chargers.index'));

    $response->assertOk()->assertSee($chargers[0]->id);
});

test('it stores the charger', function () {
    $data = Charger::factory()
        ->make()
        ->toArray();

    $response = $this->postJson(route('api.chargers.store'), $data);

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('chargers', $data);

    $response->assertStatus(201)->assertJsonFragment($data);
});

test('it updates the charger', function () {
    $charger = Charger::factory()->create();

    $currentCharger = CurrentCharger::factory()->create();
    $typeCharger = TypeCharger::factory()->create();
    $powerCharger = PowerCharger::factory()->create();
    $chargerLocation = ChargerLocation::factory()->create();

    $data = [
        'unit' => fake()->numberBetween(1, 2),
        'created_at' => fake()->dateTime(),
        'updated_at' => fake()->dateTime(),
        'deleted_at' => fake()->dateTime(),
        'current_charger_id' => $currentCharger->id,
        'type_charger_id' => $typeCharger->id,
        'power_charger_id' => $powerCharger->id,
        'charger_location_id' => $chargerLocation->id,
    ];

    $response = $this->putJson(route('api.chargers.update', $charger), $data);

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $data['id'] = $charger->id;

    $this->assertDatabaseHas('chargers', $data);

    $response->assertStatus(200)->assertJsonFragment($data);
});

test('it deletes the charger', function () {
    $charger = Charger::factory()->create();

    $response = $this->deleteJson(route('api.chargers.destroy', $charger));

    $this->assertSoftDeleted($charger);

    $response->assertNoContent();
});
