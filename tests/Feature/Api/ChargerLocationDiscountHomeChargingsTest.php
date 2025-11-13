<?php

namespace Tests\Feature\Api;

use App\Models\ChargerLocation;
use App\Models\DiscountHomeCharging;

class ChargerLocationDiscountHomeChargingsTest extends ApiTestCase
{
    public function test_it_gets_charger_location_discount_home_chargings(): void
    {
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
    }

    public function test_it_stores_the_charger_location_discount_home_chargings(): void
    {
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

        unset($data['created_at'], $data['updated_at']);

        $this->assertDatabaseHas('discount_home_chargings', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $discountHomeCharging = DiscountHomeCharging::latest('id')->first();

        $this->assertEquals(
            $chargerLocation->id,
            $discountHomeCharging->charger_location_id
        );
    }
}

