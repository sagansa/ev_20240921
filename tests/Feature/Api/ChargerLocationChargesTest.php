<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\ChargerLocation;

class ChargerLocationChargesTest extends ApiTestCase
{
    public function test_it_gets_charger_location_charges(): void
    {
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
    }

    public function test_it_stores_the_charger_location_charges(): void
    {
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

        unset($data['created_at'], $data['updated_at'], $data['deleted_at'], $data['image_start'], $data['image_finish']);

        $this->assertDatabaseHas('charges', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $charge = Charge::latest('id')->first();

        $this->assertEquals($chargerLocation->id, $charge->charger_location_id);
    }
}

