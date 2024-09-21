<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\ChargerLocation;
use App\Models\DiscountHomeCharging;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->withoutExceptionHandling();

    $user = User::factory()->create(['email' => 'admin@admin.com']);

    Sanctum::actingAs($user, [], 'web');
});

test('it gets charger_location discount_home_chargings', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $discountHomeChargings = DiscountHomeCharging::factory()
        ->count(2)
        ->create([
            'charger_location_id' => $chargerLocation->id,
        ]);

    $response = $this->getJson(
        route(
            'api.charger-locations.discount-home-chargings.index',
            $chargerLocation
        )
    );

    $response
        ->assertOk()
        ->assertSee($discountHomeChargings[0]->charger_location_id);
});

test('it stores the charger_location discount_home_chargings', function () {
    $chargerLocation = ChargerLocation::factory()->create();
    $data = DiscountHomeCharging::factory()
        ->make([
            'charger_location_id' => $chargerLocation->id,
        ])
        ->toArray();

    $response = $this->postJson(
        route(
            'api.charger-locations.discount-home-chargings.store',
            $chargerLocation
        ),
        $data
    );

    unset($data['created_at']);
    unset($data['updated_at']);

    $this->assertDatabaseHas('discount_home_chargings', $data);

    $response->assertStatus(201)->assertJsonFragment($data);

    $discountHomeCharging = DiscountHomeCharging::latest('id')->first();

    $this->assertEquals(
        $chargerLocation->id,
        $discountHomeCharging->charger_location_id
    );
});
