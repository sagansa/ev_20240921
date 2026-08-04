<?php

namespace Tests\Feature\Api;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\User;
use App\Models\Vehicle;

class VehicleTest extends ApiTestCase
{
    public function test_it_gets_vehicles_list_for_authenticated_user(): void
    {
        $otherUser = User::factory()->create();

        $brand = BrandVehicle::factory()->create();
        $model = ModelVehicle::factory()->create(['brand_vehicle_id' => $brand->id]);

        $myVehicle = Vehicle::create([
            'user_id' => $this->authUser->id,
            'brand_vehicle_id' => $brand->id,
            'model_vehicle_id' => $model->id,
            'license_plate' => 'B 1234 EV',
            'status' => 1,
        ]);

        Vehicle::create([
            'user_id' => $otherUser->id,
            'brand_vehicle_id' => $brand->id,
            'model_vehicle_id' => $model->id,
            'license_plate' => 'B 9999 EV',
            'status' => 1,
        ]);

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['license_plate' => 'B 1234 EV'])
            ->assertJsonMissing(['license_plate' => 'B 9999 EV']);
    }

    public function test_it_returns_vehicle_dropdown_options(): void
    {
        $brand = BrandVehicle::factory()->create(['name' => 'Hyundai']);
        $model = ModelVehicle::factory()->create(['brand_vehicle_id' => $brand->id, 'name' => 'Ioniq 5']);
        $type = TypeVehicle::factory()->create(['model_vehicle_id' => $model->id, 'name' => 'Long Range']);

        $response = $this->getJson('/api/v1/vehicles/options');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'Hyundai'])
            ->assertJsonFragment(['name' => 'Ioniq 5'])
            ->assertJsonFragment(['name' => 'Long Range']);
    }

    public function test_it_stores_vehicle(): void
    {
        $brand = BrandVehicle::factory()->create();
        $model = ModelVehicle::factory()->create(['brand_vehicle_id' => $brand->id]);

        $response = $this->postJson('/api/v1/vehicles', [
            'brand_vehicle_id' => $brand->id,
            'model_vehicle_id' => $model->id,
            'license_plate' => 'B 8888 EV',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'license_plate' => 'B 8888 EV',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $this->authUser->id,
            'license_plate' => 'B 8888 EV',
        ]);
    }

    public function test_it_deletes_vehicle(): void
    {
        $brand = BrandVehicle::factory()->create();
        $model = ModelVehicle::factory()->create(['brand_vehicle_id' => $brand->id]);

        $vehicle = Vehicle::create([
            'user_id' => $this->authUser->id,
            'brand_vehicle_id' => $brand->id,
            'model_vehicle_id' => $model->id,
            'license_plate' => 'B 7777 EV',
            'status' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
