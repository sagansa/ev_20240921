<?php

namespace Tests\Feature\Api;

use App\Models\Battery;
use App\Models\Charge;
use App\Models\StateOfHealth;
use App\Models\TypeVehicle;
use App\Models\User;
use App\Models\Vehicle;

class BatteryTest extends ApiTestCase
{
    public function test_it_lists_only_authenticated_users_batteries(): void
    {
        $otherUser = User::factory()->create();
        $myVehicle = Vehicle::factory()->for($this->authUser)->create();
        $otherVehicle = Vehicle::factory()->for($otherUser)->create();

        Battery::factory()->create(['user_id' => $this->authUser->id, 'vehicle_id' => $myVehicle->id, 'label' => 'Battery A']);
        Battery::factory()->create(['user_id' => $otherUser->id, 'vehicle_id' => $otherVehicle->id, 'label' => 'Battery Lain']);

        $response = $this->getJson('/api/v1/batteries');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Battery A');
    }

    public function test_it_filters_batteries_by_vehicle_and_active(): void
    {
        $vehicleA = Vehicle::factory()->for($this->authUser)->create();
        $vehicleB = Vehicle::factory()->for($this->authUser)->create();

        Battery::factory()->create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleA->id, 'label' => 'Active A']);
        Battery::factory()->retired()->create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleA->id, 'label' => 'Retired A']);
        Battery::factory()->create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicleB->id, 'label' => 'Active B']);

        $this->getJson('/api/v1/batteries?vehicle_id='.$vehicleA->id)
            ->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/batteries?vehicle_id='.$vehicleA->id.'&status=1')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Active A');

        $this->getJson('/api/v1/batteries?active=true&vehicle_id='.$vehicleA->id)
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_creates_battery_with_default_capacity_from_type_vehicle(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 30.5]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create(['type_vehicle_id' => $type->id]);

        $response = $this->postJson('/api/v1/batteries', [
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery A',
            'installed_at' => '2026-08-01',
            'installed_km' => 12000,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'label' => 'Battery A',
                    'capacity_kwh' => 30.5,
                    'status' => 1,
                    'installed_km' => 12000,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('batteries', [
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery A',
        ]);
    }

    public function test_it_rejects_creating_battery_for_others_vehicle(): void
    {
        $otherUser = User::factory()->create();
        $otherVehicle = Vehicle::factory()->for($otherUser)->create();

        $this->postJson('/api/v1/batteries', [
            'vehicle_id' => $otherVehicle->id,
            'installed_at' => '2026-08-01',
        ])->assertStatus(403);

        $this->assertDatabaseCount('batteries', 0);
    }

    public function test_it_rejects_creating_second_active_battery_for_same_vehicle(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 30.5]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create(['type_vehicle_id' => $type->id]);
        // Vehicle sudah punya 1 baterai aktif.
        Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'status' => 1,
            'removed_at' => null,
        ]);

        $this->postJson('/api/v1/batteries', [
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery B',
            'installed_at' => '2026-08-05',
        ])->assertStatus(422)
            ->assertJson(['success' => false]);

        // Baterai lama tetap satu-satunya yg aktif.
        $this->assertDatabaseCount('batteries', 1);
    }

    public function test_update_rejects_reactivating_when_other_active_exists(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 30.5]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create(['type_vehicle_id' => $type->id]);

        // Baterai lama (retired) + baterai aktif baru (via swap-style state).
        $retired = Battery::factory()->retired()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
        ]);
        Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'status' => 1,
            'removed_at' => null,
        ]);

        // Mencoba mengaktifkan kembali baterai retired → ditolak (invariant).
        $this->putJson("/api/v1/batteries/{$retired->id}", [
            'status' => 1,
            'removed_at' => null,
        ])->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_it_swaps_battery_atomically(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 30.5]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create(['type_vehicle_id' => $type->id]);
        $old = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery Original',
            'installed_km' => 1000,
            'status' => 1,
        ]);

        $response = $this->postJson("/api/v1/vehicles/{$vehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 15000,
            'new_label' => 'Battery Baru',
            'new_serial_number' => 'SN-999',
            'note' => 'Ganti karena drop',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $old->refresh();
        $this->assertSame(0, $old->status);
        $this->assertSame('2026-08-09', $old->removed_at?->toDateString());
        $this->assertSame(15000, $old->removed_km);

        $new = Battery::where('vehicle_id', $vehicle->id)->where('id', '!=', $old->id)->first();
        $this->assertNotNull($new);
        $this->assertSame(1, $new->status);
        $this->assertSame('2026-08-09', $new->installed_at?->toDateString());
        $this->assertSame(15000, $new->installed_km);
        $this->assertSame(30.5, (float) $new->capacity_kwh);
        $this->assertSame('SN-999', $new->serial_number);

        $this->assertSame($old->id, $response->json('data.old.id'));
        $this->assertSame($new->id, $response->json('data.new.id'));
    }

    public function test_swap_fails_when_no_active_battery(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        Battery::factory()->retired()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->postJson("/api/v1/vehicles/{$vehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 15000,
        ])->assertStatus(422);

        $this->assertDatabaseCount('batteries', 1);
    }

    public function test_swap_rejects_others_vehicle(): void
    {
        $otherUser = User::factory()->create();
        $otherVehicle = Vehicle::factory()->for($otherUser)->create();

        $this->postJson("/api/v1/vehicles/{$otherVehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 15000,
        ])->assertStatus(403);
    }

    public function test_swap_keeps_historical_charges_untouched(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $old = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery Original',
            'installed_km' => 1000,
            'status' => 1,
        ]);
        $charge = Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'battery_id' => $old->id,
            'date' => '2026-08-01',
            'km_now' => 10000,
        ]);

        $this->postJson("/api/v1/vehicles/{$vehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 15000,
        ])->assertOk();

        $charge->refresh();
        $this->assertSame($old->id, $charge->battery_id);
    }

    public function test_store_charge_auto_assigns_active_battery(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $battery = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery Original',
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/charging-sessions', [
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-09',
            'kwh' => 20,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('charges', [
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
        ]);
        $response->assertJsonPath('data.battery_id', $battery->id);
    }

    public function test_store_charge_auto_assigns_new_battery_after_swap(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Original',
            'installed_km' => 1000,
            'status' => 1,
        ]);

        $swap = $this->postJson("/api/v1/vehicles/{$vehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 15000,
            'new_label' => 'Battery Baru',
        ]);
        $swap->assertOk();
        $newBatteryId = $swap->json('data.new.id');

        $this->postJson('/api/v1/charging-sessions', [
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-10',
            'kwh' => 20,
        ])->assertStatus(201);

        $this->assertDatabaseHas('charges', [
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'battery_id' => $newBatteryId,
        ]);
    }

    public function test_store_soh_auto_resolves_battery_by_km(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $original = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Original',
            'installed_km' => null,
            'status' => 1,
        ]);
        $this->postJson("/api/v1/vehicles/{$vehicle->id}/swap-battery", [
            'date' => '2026-08-09',
            'km' => 5000,
            'new_label' => 'Battery Baru',
        ])->assertOk();
        $newBatteryId = Battery::where('vehicle_id', $vehicle->id)->where('id', '!=', $original->id)->first()->id;

        // km sebelum swap → battery original.
        $this->postJson('/api/v1/state-of-health', [
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-01',
            'km' => 3000,
            'percentage' => 92,
        ])->assertStatus(201);
        $this->assertDatabaseHas('state_of_healths', [
            'vehicle_id' => $vehicle->id,
            'km' => 3000,
            'battery_id' => $original->id,
        ]);

        // km setelah swap → battery baru.
        $this->postJson('/api/v1/state-of-health', [
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-10',
            'km' => 8000,
            'percentage' => 85,
        ])->assertStatus(201);
        $this->assertDatabaseHas('state_of_healths', [
            'vehicle_id' => $vehicle->id,
            'km' => 8000,
            'battery_id' => $newBatteryId,
        ]);
    }

    public function test_store_soh_accepts_explicit_battery_id(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $battery = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'status' => 1,
        ]);

        $this->postJson('/api/v1/state-of-health', [
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
            'date' => '2026-08-01',
            'km' => 5000,
            'percentage' => 90,
        ])->assertStatus(201);

        $this->assertDatabaseHas('state_of_healths', [
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
        ]);
    }

    public function test_trend_analysis_filters_by_battery(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $batteryA = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'A',
            'status' => 1,
        ]);
        $batteryB = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'B',
            'status' => 1,
        ]);

        StateOfHealth::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicle->id, 'battery_id' => $batteryA->id, 'date' => '2026-08-01', 'km' => 1000, 'percentage' => 95]);
        StateOfHealth::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicle->id, 'battery_id' => $batteryA->id, 'date' => '2026-08-02', 'km' => 2000, 'percentage' => 93]);
        StateOfHealth::create(['user_id' => $this->authUser->id, 'vehicle_id' => $vehicle->id, 'battery_id' => $batteryB->id, 'date' => '2026-08-03', 'km' => 3000, 'percentage' => 88]);

        $this->getJson("/api/v1/state-of-health/{$vehicle->id}/trend-analysis?battery_id={$batteryA->id}")
            ->assertOk()
            ->assertJsonPath('data.summary.total_records', 2)
            ->assertJsonPath('data.summary.initial_percentage', 95)
            ->assertJsonPath('data.summary.latest_percentage', 93);
    }

    public function test_backfill_is_idempotent(): void
    {
        $type = TypeVehicle::factory()->create(['battery_capacity' => 25.0]);
        $vehicle = Vehicle::factory()->for($this->authUser)->create(['type_vehicle_id' => $type->id]);
        Charge::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-01',
            'km_now' => 10000,
        ]);
        StateOfHealth::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'date' => '2026-08-01',
            'km' => 9000,
            'percentage' => 95,
        ]);

        $migration = require database_path('migrations/2026_08_09_000004_backfill_batteries_default.php');
        $migration->up();
        $migration->up(); // run ulang → idempoten

        $this->assertSame(1, Battery::where('vehicle_id', $vehicle->id)->count());
        $battery = Battery::where('vehicle_id', $vehicle->id)->first();
        $this->assertSame('Original', $battery->label);
        $this->assertEqualsWithDelta(25.0, (float) $battery->capacity_kwh, 0.001);
        $this->assertSame(1, $battery->status);

        $this->assertDatabaseHas('charges', [
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
        ]);
        $this->assertDatabaseHas('state_of_healths', [
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
        ]);
    }

    public function test_soh_resource_exposes_battery(): void
    {
        $vehicle = Vehicle::factory()->for($this->authUser)->create();
        $battery = Battery::factory()->create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'label' => 'Battery A',
            'status' => 1,
        ]);
        StateOfHealth::create([
            'user_id' => $this->authUser->id,
            'vehicle_id' => $vehicle->id,
            'battery_id' => $battery->id,
            'date' => '2026-08-01',
            'km' => 5000,
            'percentage' => 90,
        ]);

        $this->getJson('/api/v1/state-of-health')
            ->assertOk()
            ->assertJsonPath('data.0.battery_id', $battery->id)
            ->assertJsonPath('data.0.battery.label', 'Battery A')
            ->assertJsonPath('data.0.vehicle_id', $vehicle->id);
    }
}
