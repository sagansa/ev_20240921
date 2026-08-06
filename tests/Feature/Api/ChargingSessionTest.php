<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\Charger;
use App\Models\ChargingStation;
use App\Models\ChargingStationCharger;
use App\Models\ModelVehicle;
use App\Models\TypeCharger;
use App\Models\TypeVehicle;
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

    // ─────────────────────────────────────────────────────────────────────
    // Filter kombinasi model + AC/DC — repro bug "tidak terjadi apapun".
    // ─────────────────────────────────────────────────────────────────────

    public function test_filter_by_model_vehicle_id_returns_only_matching_sessions(): void
    {
        [$matchingModel, $otherModel] = ModelVehicle::factory()->count(2)->create();

        $vehicleA = Vehicle::factory()->for($this->authUser)->create([
            'model_vehicle_id' => $matchingModel->id,
        ]);
        $vehicleB = Vehicle::factory()->for($this->authUser)->create([
            'model_vehicle_id' => $otherModel->id,
        ]);

        Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicleA->id,
            'date' => now()->toDateString(),
            'kWh' => 10,
        ]);
        Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicleB->id,
            'date' => now()->toDateString(),
            'kWh' => 20,
        ]);

        $response = $this->getJson("/api/v1/charging-sessions?model_vehicle_id={$matchingModel->id}");

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle_id', $vehicleA->id);
    }

    public function test_filter_by_charging_type_dc_uses_snapshot_columns(): void
    {
        // Sesi DC — nama station snapshot mengandung token DC.
        Charge::create([
            'user_id' => $this->authUser->id,
            'station_name_snapshot' => 'SPKLU PLN DC Fast',
            'date' => now()->toDateString(),
            'kWh' => 10,
        ]);
        // Sesi AC — nama station snapshot generik (tidak mengandung token DC).
        Charge::create([
            'user_id' => $this->authUser->id,
            'station_name_snapshot' => 'SPKLU Rumah',
            'date' => now()->toDateString(),
            'kWh' => 5,
        ]);

        $response = $this->getJson('/api/v1/charging-sessions?charging_type=DC');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU PLN DC Fast');
    }

    public function test_filter_by_charging_type_ac_excludes_dc_sessions(): void
    {
        Charge::create([
            'user_id' => $this->authUser->id,
            'station_name_snapshot' => 'SPKLU PLN DC Fast',
            'date' => now()->toDateString(),
            'kWh' => 10,
        ]);
        Charge::create([
            'user_id' => $this->authUser->id,
            'station_name_snapshot' => 'SPKLU Rumah',
            'date' => now()->toDateString(),
            'kWh' => 5,
        ]);

        $response = $this->getJson('/api/v1/charging-sessions?charging_type=AC');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU Rumah');
    }

    public function test_filter_combination_model_and_charging_type(): void
    {
        // Skenario user: "DC + Wuling Air Ev" → hanya sesi yang cocok keduanya.
        $airEv = ModelVehicle::factory()->create(['name' => 'Air Ev']);
        $tesla = ModelVehicle::factory()->create(['name' => 'Model 3']);
        $vehicleA = Vehicle::factory()->for($this->authUser)->create(['model_vehicle_id' => $airEv->id]);
        $vehicleB = Vehicle::factory()->for($this->authUser)->create(['model_vehicle_id' => $tesla->id]);

        Charge::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleA->id, 'station_name_snapshot' => 'SPKLU DC', 'date' => now()->toDateString(), 'kWh' => 10]);
        Charge::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleA->id, 'station_name_snapshot' => 'SPKLU AC Rumah', 'date' => now()->toDateString(), 'kWh' => 5]);
        Charge::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleB->id, 'station_name_snapshot' => 'SPKLU DC', 'date' => now()->toDateString(), 'kWh' => 20]);

        $response = $this->getJson("/api/v1/charging-sessions?model_vehicle_id={$airEv->id}&charging_type=DC");

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle.model_vehicle.name', 'Air Ev')
            ->assertJsonPath('data.0.station_name', 'SPKLU DC');
    }

    public function test_filter_by_charging_type_dc_via_canonical_station_type_charge(): void
    {
        // Skenario REAL: sesi terhubung ke charging_station canonical yang punya
        // type_charge eksplisit. Nama station TIDAK mengandung token "DC/FAST/CCS"
        // (mis. "SPKLU PLN Senayan") — heuristic substring akan salah klasifikasi,
        // tapi type_charge canonical harus tetap jadi sumber kebenaran.
        $dcStation = ChargingStation::create([
            'source' => 'esdm', 'nama_lokasi' => 'SPKLU PLN Senayan',
            'latitude' => -6.2, 'longitude' => 106.8, 'type_charge' => 'ultra_fast',
        ]);
        ChargingStationCharger::create([
            'station_id' => $dcStation->id, 'type_charge' => 'ultra_fast',
            'nama' => 'ABB Terra 184', 'jumlah_charger' => 1, 'jumlah_konektor' => 2,
        ]);
        $acStation = ChargingStation::create([
            'source' => 'esdm', 'nama_lokasi' => 'SPKLU Hotel',
            'latitude' => -6.2, 'longitude' => 106.8, 'type_charge' => 'medium',
        ]);
        ChargingStationCharger::create([
            'station_id' => $acStation->id, 'type_charge' => 'medium',
            'nama' => 'Wallbox Pulsar', 'jumlah_charger' => 1, 'jumlah_konektor' => 1,
        ]);

        Charge::create([
            'user_id' => $this->authUser->id, 'charging_station_id' => $dcStation->id,
            'station_name_snapshot' => 'SPKLU PLN Senayan',
            'date' => now()->toDateString(), 'kWh' => 30,
        ]);
        Charge::create([
            'user_id' => $this->authUser->id, 'charging_station_id' => $acStation->id,
            'station_name_snapshot' => 'SPKLU Hotel',
            'date' => now()->toDateString(), 'kWh' => 7,
        ]);

        // Filter DC → hanya sesi di station DC canonical.
        $dc = $this->getJson('/api/v1/charging-sessions?charging_type=DC');
        $dc->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU PLN Senayan');

        // Filter AC → hanya sesi di station AC canonical.
        $ac = $this->getJson('/api/v1/charging-sessions?charging_type=AC');
        $ac->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU Hotel');
    }

    public function test_filter_by_charging_type_via_charger_type_charger_enum(): void
    {
        // Skenario REAL ala input Filament admin: user memilih Charger spesifik
        // (charger_id) saat mencatat sesi. Charger tersebut ber-relasi ke
        // TypeCharger dgn name enum 'AC'/'DC' — ini sumber kebenaran definitif.
        // Nama station snapshot bisa apa saja (tidak relevan).
        $dcType = TypeCharger::factory()->create(['name' => 'DC']);
        $acType = TypeCharger::factory()->create(['name' => 'AC']);
        $dcCharger = Charger::factory()->create(['type_charger_id' => $dcType->id]);
        $acCharger = Charger::factory()->create(['type_charger_id' => $acType->id]);

        Charge::create([
            'user_id' => $this->authUser->id, 'charger_id' => $dcCharger->id,
            'station_name_snapshot' => 'SPKLU Pertamina',  // nama generik, bukti heuristic substring salah
            'date' => now()->toDateString(), 'kWh' => 40,
        ]);
        Charge::create([
            'user_id' => $this->authUser->id, 'charger_id' => $acCharger->id,
            'station_name_snapshot' => 'SPKLU Mall',
            'date' => now()->toDateString(), 'kWh' => 6,
        ]);

        // Filter DC → hanya sesi dgn charger type DC.
        $this->getJson('/api/v1/charging-sessions?charging_type=DC')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU Pertamina');

        // Filter AC → hanya sesi dgn charger type AC.
        $this->getJson('/api/v1/charging-sessions?charging_type=AC')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.station_name', 'SPKLU Mall');
    }

    public function test_resource_exposes_charging_type_derived_from_type_charger(): void
    {
        // Resource mengekspos charging_type deterministik (AC/DC) — turunan
        // cascade yang sama dgn filter. UI pakai ini utk badge tanpa heuristic.
        $dcType = TypeCharger::factory()->create(['name' => 'DC']);
        $dcCharger = Charger::factory()->create(['type_charger_id' => $dcType->id]);

        Charge::create([
            'user_id' => $this->authUser->id, 'charger_id' => $dcCharger->id,
            'station_name_snapshot' => 'SPKLU Pertamina',
            'date' => now()->toDateString(), 'kWh' => 40,
        ]);

        $this->getJson('/api/v1/charging-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.charging_type', 'DC');
    }

    public function test_charging_session_resource_includes_model_vehicle(): void
    {
        $model = ModelVehicle::factory()->create(['name' => 'Air Ev']);
        $type = TypeVehicle::factory()->create(['model_vehicle_id' => $model->id]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create([
            'model_vehicle_id' => $model->id,
            'type_vehicle_id' => $type->id,
        ]);
        Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'date' => now()->toDateString(),
            'kWh' => 10,
        ]);

        $response = $this->getJson('/api/v1/charging-sessions');

        $response->assertOk();
        // Client (VehicleDto.modelVehicle) memerlukan field ini — tanpa ini,
        // filter modelVehicleId di mobile tidak pernah match (selalu null).
        $response->assertJsonPath('data.0.vehicle.model_vehicle.id', $model->id);
        $response->assertJsonPath('data.0.vehicle.model_vehicle.name', 'Air Ev');
    }
}
