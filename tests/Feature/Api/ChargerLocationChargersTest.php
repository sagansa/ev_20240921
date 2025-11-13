<?php

namespace Tests\Feature\Api;

use App\Models\Charger;
use App\Models\ChargerLocation;

class ChargerLocationChargersTest extends ApiTestCase
{
    public function test_it_gets_charger_location_chargers(): void
    {
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
    }

    public function test_it_stores_the_charger_location_chargers(): void
    {
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

        unset($data['merk_charger_id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $this->assertDatabaseHas('chargers', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $charger = Charger::latest('id')->first();

        $this->assertEquals($chargerLocation->id, $charger->charger_location_id);
    }
}

