<?php

use App\Models\User;
use App\Models\Charge;
use App\Models\Charger;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets charger charges', function () {
    $charger = Charger::factory()->create();
    $charges = Charge::factory()
        ->count(2)
        ->create([
            'charger_id' => $charger->id,
        ]);

    $response = $this->getJson(route('api.chargers.charges.index', $charger));

    $response->assertOk()->assertSee($charges[0]->id);
});

test('it stores the charger charges', function () {
    $charger = Charger::factory()->create();
    $data = Charge::factory()
        ->make([
            'charger_id' => $charger->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route('api.chargers.charges.store', $charger),
        $data
    );

    unset($data['created_at']);
    unset($data['updated_at']);
    unset($data['deleted_at']);

    $this->assertDatabaseHas('charges', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $charge = Charge::latest('id')->first();

    $this->assertEquals($charger->id, $charge->charger_id);
});
