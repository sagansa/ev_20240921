<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\ChargingStation;
use App\Models\User;
use App\Models\Vehicle;

class ChargingSessionTest extends ApiTestCase
{
    public function test_it_lists_only_authenticated_users_charging_sessions(): void
    {
        $otherUser = User::factory()->create();

        Charge::create([
            'user_id' => $this->authUser->id,
            'station_name_snapshot' => 'SPKLU Kebon Jeruk',
            'date' => now()->toDateString(),
            'kWh' => 15.5,
            'total_cost' => 45000,
        ]);

        Charge::create([
            'user_id' => $otherUser->id,
            'station_name_snapshot' => 'SPKLU Orang Lain',
            'date' => now()->toDateString(),
            'kWh' => 30.0,
            'total_cost' => 90000,
        ]);

        $response = $this->getJson('/api/v1/charging-sessions');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['station_name' => 'SPKLU Kebon Jeruk'])
            ->assertJsonMissing(['station_name' => 'SPKLU Orang Lain']);
    }

    public function test_it_creates_charging_session_with_station_snapshot(): void
    {
        $station = ChargingStation::create([
            'source' => 'esdm',
            'nama_lokasi' => 'SPKLU Senayan',
            'alamat' => 'Jl. Asia Afrika',
            'latitude' => -6.22,
            'longitude' => 106.80,
            'provider_name' => 'PLN',
        ]);

        $response = $this->postJson('/api/v1/charging-sessions', [
            'charging_station_id' => $station->id,
            'date' => '2026-08-04',
            'kwh' => 20.0,
            'total_cost' => 60000,
            'parking' => 5000,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'station_name' => 'SPKLU Senayan',
                    'station_provider' => 'PLN',
                    'kwh' => 20.0,
                    'total_cost' => 60000,
                    'parking_cost' => 5000,
                ],
            ]);

        $this->assertDatabaseHas('charges', [
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
            'station_name_snapshot' => 'SPKLU Senayan',
        ]);
    }

    public function test_it_returns_analytics_summary(): void
    {
        Charge::create([
            'user_id' => $this->authUser->id,
            'date' => '2026-08-01',
            'kWh' => 25.0,
            'total_cost' => 70000,
            'km_before' => 1000,
            'km_now' => 1150,
        ]);

        Charge::create([
            'user_id' => $this->authUser->id,
            'date' => '2026-08-02',
            'kWh' => 35.0,
            'total_cost' => 95000,
            'km_before' => 1150,
            'km_now' => 1350,
        ]);

        $response = $this->getJson('/api/v1/charging-sessions/analytics');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_sessions' => 2,
                    'total_energy_kwh' => 60.0,
                    'total_cost' => 165000,
                    'total_distance_km' => 350.0,
                ],
            ]);
    }

    public function test_it_deletes_charging_session(): void
    {
        $session = Charge::create([
            'user_id' => $this->authUser->id,
            'date' => now()->toDateString(),
            'kWh' => 10,
        ]);

        $response = $this->deleteJson("/api/v1/charging-sessions/{$session->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('charges', ['id' => $session->id]);
    }
}
