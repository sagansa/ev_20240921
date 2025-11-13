<?php

namespace Tests\Feature\Api;

use App\Models\Charger;
use App\Models\ChargerLocation;
use App\Models\CurrentCharger;
use App\Models\MerkCharger;
use App\Models\PowerCharger;
use App\Models\TypeCharger;

class ChargerTest extends ApiTestCase
{
    public function test_it_gets_chargers_list(): void
    {
        $chargers = Charger::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('api.chargers.index'));

        $response->assertOk()->assertSee($chargers[0]->id);
    }

    public function test_it_stores_the_charger(): void
    {
        $data = Charger::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.chargers.store'), $data);

        unset($data['merk_charger_id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $this->assertDatabaseHas('chargers', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    public function test_it_updates_the_charger(): void
    {
        $charger = Charger::factory()->create();

        $currentCharger = CurrentCharger::factory()->create();
        $typeCharger = TypeCharger::factory()->create();
        $powerCharger = PowerCharger::factory()->create();
        $chargerLocation = ChargerLocation::factory()->create();
        $merkCharger = MerkCharger::factory()->create();

        $data = [
            'unit' => fake()->numberBetween(1, 2),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
            'deleted_at' => fake()->dateTime(),
            'current_charger_id' => $currentCharger->id,
            'type_charger_id' => $typeCharger->id,
            'power_charger_id' => $powerCharger->id,
            'charger_location_id' => $chargerLocation->id,
            'merk_charger_id' => $merkCharger->id,
        ];

        $response = $this->putJson(route('api.chargers.update', $charger), $data);

        unset($data['merk_charger_id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data['id'] = $charger->id;

        $this->assertDatabaseHas('chargers', $data);

        $response->assertStatus(200)->assertJsonFragment($data);
    }

    public function test_it_deletes_the_charger(): void
    {
        $charger = Charger::factory()->create();

        $response = $this->deleteJson(route('api.chargers.destroy', $charger));

        $this->assertSoftDeleted($charger);

        $response->assertNoContent();
    }
}

