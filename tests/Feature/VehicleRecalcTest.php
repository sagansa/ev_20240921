<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ChargingStation;
use App\Models\TypeVehicle;
use App\Models\Vehicle;
use Tests\Feature\Api\ApiTestCase;

class VehicleRecalcTest extends ApiTestCase
{
    public function test_updating_capacity_recalcs_estimate_sessions_only(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 77.4]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create([
            'type_vehicle_id' => $type->id,
        ]);

        // Sesi HOME estimasi — kWh & total_cost harus di-recalc (scale proporsional).
        $home = Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'is_kwh_measured' => false,
            'start_charging_now' => 20,
            'finish_charging_now' => 80,
            'kWh' => 40,
            'total_cost' => 50000,
        ]);

        // Sesi PUBLIK (SPKLU) estimasi — kWh di-recalc, cost dari struk TIDAK disentuh.
        $station = ChargingStation::create([
            'source' => 'esdm',
            'nama_lokasi' => 'SPKLU Senayan',
            'alamat' => 'Jl. Asia Afrika',
            'latitude' => -6.22,
            'longitude' => 106.80,
            'provider_name' => 'PLN',
        ]);
        $public = Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'charging_station_id' => $station->id,
            'is_kwh_measured' => false,
            'start_charging_now' => 10,
            'finish_charging_now' => 90,
            'kWh' => 50,
            'total_cost' => 150000,
        ]);

        // Sesi TERUKUR (is_kwh_measured = true) — tidak tersentuh sama sekali.
        $measured = Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'is_kwh_measured' => true,
            'start_charging_now' => 30,
            'finish_charging_now' => 100,
            'kWh' => 60,
            'total_cost' => 70000,
        ]);

        $response = $this->putJson("/api/v1/vehicles/{$vehicle->id}", [
            'battery_capacity_kwh' => 77.4,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('recalculated_sessions', 2)
            ->assertJsonPath('data.battery_capacity_kwh', 77.4);

        // Home estimasi: kWh = (80−20)/100 × 77.4 × 1.1 = 51.084; cost scale proporsional.
        $home->refresh();
        $this->assertEqualsWithDelta(51.084, (float) $home->kWh, 0.001);
        $this->assertSame(63855, (int) $home->total_cost);

        // Publik estimasi: kWh berubah, cost tetap (dari struk).
        $public->refresh();
        $this->assertEqualsWithDelta(68.112, (float) $public->kWh, 0.001);
        $this->assertSame(150000, (int) $public->total_cost);

        // Terukur: kWh & cost tidak tersentuh.
        $measured->refresh();
        $this->assertSame(60.0, (float) $measured->kWh);
        $this->assertSame(70000, (int) $measured->total_cost);
    }

    public function test_effective_capacity_accessor_prefers_column_over_type(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 64.0]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create([
            'type_vehicle_id' => $type->id,
        ]);

        // Fallback ke typeVehicle bila kolom kosong.
        $this->assertSame(64.0, $vehicle->battery_capacity_kwh);

        // Kolom menang bila terisi.
        $vehicle->update(['battery_capacity_kwh' => 77.0]);
        $this->assertSame(77.0, $vehicle->battery_capacity_kwh);

        // Kolom dikosongkan → kembali fallback ke typeVehicle.
        $vehicle->update(['battery_capacity_kwh' => null]);
        $this->assertSame(64.0, $vehicle->battery_capacity_kwh);
    }

    public function test_no_recalc_when_capacity_unchanged(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create([
            'battery_capacity_kwh' => 60.0,
        ]);

        Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'is_kwh_measured' => false,
            'start_charging_now' => 20,
            'finish_charging_now' => 80,
            'kWh' => 40,
            'total_cost' => 50000,
        ]);

        $response = $this->putJson("/api/v1/vehicles/{$vehicle->id}", [
            'license_plate' => 'B 1234 EV',
            'battery_capacity_kwh' => 60.0,
        ]);

        $response->assertOk()
            ->assertJsonPath('recalculated_sessions', 0);

        $this->assertEqualsWithDelta(60.0, $response->json('data.battery_capacity_kwh'), 0.001);

        $this->assertDatabaseHas('charges', [
            'vehicle_id' => $vehicle->id,
            'kWh' => 40,
        ]);
    }
}
