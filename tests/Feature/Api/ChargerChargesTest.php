<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\Charger;

class ChargerChargesTest extends ApiTestCase
{
    public function test_it_gets_charger_charges(): void
    {
        $charger = Charger::factory()->create();
        $charges = Charge::factory()
            ->count(2)
            ->create([
                'charger_id' => $charger->id,
            ]);

        $response = $this->getJson(route('api.chargers.charges.index', $charger));

        $response->assertOk()->assertSee($charges[0]->id);
    }

    public function test_it_stores_the_charger_charges(): void
    {
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

        unset($data['created_at'], $data['updated_at'], $data['deleted_at'], $data['image_start'], $data['image_finish']);

        $this->assertDatabaseHas('charges', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $charge = Charge::latest('id')->first();

        $this->assertEquals($charger->id, $charge->charger_id);
    }
}

