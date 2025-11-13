<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\Vehicle;

class VehicleChargesTest extends ApiTestCase
{
    public function test_it_gets_vehicle_charges(): void
    {
        $vehicle = Vehicle::factory()->create();
        $charges = Charge::factory()
            ->count(2)
            ->create([
                'vehicle_id' => $vehicle->id,
            ]);

        $response = $this->getJson(route('api.vehicles.charges.index', $vehicle));

        $response->assertOk()->assertSee($charges[0]->id);
    }

    public function test_it_stores_the_vehicle_charges(): void
    {
        $vehicle = Vehicle::factory()->create();
        $data = Charge::factory()
            ->make([
                'vehicle_id' => $vehicle->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.vehicles.charges.store', $vehicle),
            $data
        );

        unset($data['created_at'], $data['updated_at'], $data['deleted_at'], $data['image_start'], $data['image_finish']);

        $this->assertDatabaseHas('charges', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $charge = Charge::latest('id')->first();

        $this->assertEquals($vehicle->id, $charge->vehicle_id);
    }
}

