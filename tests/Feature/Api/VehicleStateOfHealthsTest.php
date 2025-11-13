<?php

namespace Tests\Feature\Api;

use App\Models\StateOfHealth;
use App\Models\Vehicle;

class VehicleStateOfHealthsTest extends ApiTestCase
{
    public function test_it_gets_vehicle_state_of_healths(): void
    {
        $vehicle = Vehicle::factory()->create();
        $stateOfHealths = StateOfHealth::factory()
            ->count(2)
            ->create([
                'vehicle_id' => $vehicle->id,
            ]);

        $response = $this->getJson(
            route('api.vehicles.state-of-healths.index', $vehicle)
        );

        $response->assertOk()->assertSee($stateOfHealths[0]->id);
    }

    public function test_it_stores_the_vehicle_state_of_healths(): void
    {
        $vehicle = Vehicle::factory()->create();
        $data = StateOfHealth::factory()
            ->make([
                'vehicle_id' => $vehicle->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.vehicles.state-of-healths.store', $vehicle),
            $data
        );

        unset($data['date'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $this->assertDatabaseHas('state_of_healths', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $stateOfHealth = StateOfHealth::latest('id')->first();

        $this->assertEquals($vehicle->id, $stateOfHealth->vehicle_id);
    }
}

