<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\Charger;
use App\Models\ChargerLocation;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

class ChargeTest extends ApiTestCase
{
    private function endpoint(string $path = ''): string
    {
        $base = '/api/v1/charging-sessions';

        return $path ? "{$base}/{$path}" : $base;
    }

    public function test_it_gets_charges_list(): void
    {
        $vehicle = Vehicle::factory()
            ->for($this->authUser, 'user')
            ->create();

        $charges = Charge::factory()
            ->count(2)
            ->for($vehicle)
            ->create([
                'user_id' => $this->authUser->id,
            ]);

        $response = $this->getJson($this->endpoint());

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $charges->first()->id,
            ]);
    }

    public function test_it_stores_the_charge(): void
    {
        $vehicle = Vehicle::factory()
            ->for($this->authUser, 'user')
            ->create();

        $chargerLocation = ChargerLocation::factory()->create();
        $charger = Charger::factory()
            ->for($chargerLocation)
            ->create();

        $payload = [
            'vehicle_id' => $vehicle->id,
            'charger_location_id' => $chargerLocation->id,
            'charger_id' => $charger->id,
            'date' => Carbon::now()->toDateString(),
            'km_before' => 100,
            'km_now' => 150,
            'start_charging_now' => 60,
            'finish_charging_now' => 90,
            'finish_charging_before' => 50,
            'parking' => 10,
        ];

        $response = $this->postJson($this->endpoint(), $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'vehicle_id' => $vehicle->id,
                'charger_location_id' => $chargerLocation->id,
                'charger_id' => $charger->id,
                'user_id' => $this->authUser->id,
            ]);

        $this->assertDatabaseHas('charges', [
            'vehicle_id' => $vehicle->id,
            'charger_location_id' => $chargerLocation->id,
            'charger_id' => $charger->id,
            'user_id' => $this->authUser->id,
        ]);
    }

    public function test_it_updates_the_charge(): void
    {
        $vehicle = Vehicle::factory()
            ->for($this->authUser, 'user')
            ->create();

        $charge = Charge::factory()
            ->for($vehicle)
            ->create([
                'user_id' => $this->authUser->id,
            ]);
        $this->assertSame($this->authUser->id, $charge->user_id);

        $payload = [
            'km_now' => 250,
            'km_before' => 200,
            'start_charging_now' => 120,
            'finish_charging_before' => 80,
            'total_cost' => 75000,
        ];

        $response = $this->putJson($this->endpoint($charge->id), $payload);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $charge->id,
                'km_now' => $payload['km_now'],
                'total_cost' => $payload['total_cost'],
            ]);

        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'km_now' => $payload['km_now'],
            'km_before' => $payload['km_before'],
            'total_cost' => $payload['total_cost'],
        ]);
    }

    public function test_it_deletes_the_charge(): void
    {
        $charge = Charge::factory()
            ->for(Vehicle::factory()->for($this->authUser, 'user'))
            ->create([
                'user_id' => $this->authUser->id,
            ]);
        $this->assertSame($this->authUser->id, $charge->user_id);

        $response = $this->deleteJson($this->endpoint($charge->id));

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertSoftDeleted('charges', ['id' => $charge->id]);
    }
}
