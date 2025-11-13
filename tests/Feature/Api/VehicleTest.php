<?php

namespace Tests\Feature\Api;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\User;
use App\Models\Vehicle;

class VehicleTest extends ApiTestCase
{
    public function test_it_gets_vehicles_list(): void
    {
        $vehicles = Vehicle::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('api.vehicles.index'));

        $response->assertOk()->assertSee($vehicles[0]->id);
    }

    public function test_it_stores_the_vehicle(): void
    {
        $data = Vehicle::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.vehicles.store'), $data);

        unset($data['image'], $data['user_id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $this->assertDatabaseHas('vehicles', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    public function test_it_updates_the_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $brandVehicle = BrandVehicle::factory()->create();
        $modelVehicle = ModelVehicle::factory()->create();
        $typeVehicle = TypeVehicle::factory()->create();
        $user = User::factory()->create();

        $data = [
            'image' => fake()->word(),
            'license_plate' => fake()->name(),
            'ownership' => fake()->date(),
            'status' => fake()->numberBetween(1, 2),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
            'deleted_at' => fake()->dateTime(),
            'brand_vehicle_id' => $brandVehicle->id,
            'model_vehicle_id' => $modelVehicle->id,
            'type_vehicle_id' => $typeVehicle->id,
            'user_id' => $user->id,
        ];

        $response = $this->putJson(route('api.vehicles.update', $vehicle), $data);

        unset($data['image'], $data['user_id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data['id'] = $vehicle->id;

        $this->assertDatabaseHas('vehicles', $data);

        $response->assertStatus(200)->assertJsonFragment($data);
    }

    public function test_it_deletes_the_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->deleteJson(route('api.vehicles.destroy', $vehicle));

        $this->assertSoftDeleted($vehicle);

        $response->assertNoContent();
    }
}

