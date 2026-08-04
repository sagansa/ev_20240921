<?php

namespace Tests\Feature\Api;

use App\Models\ChargingStation;
use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class LogbookEntryTest extends ApiTestCase
{
    public function test_it_lists_only_authenticated_users_entries_sorted_desc(): void
    {
        $otherUser = User::factory()->create();

        LogbookEntry::create([
            'user_id' => $this->authUser->id,
            'station_name' => 'SPKLU Aku',
            'session_at' => now()->subDay(),
            'energy_kwh' => 10,
        ]);

        LogbookEntry::create([
            'user_id' => $this->authUser->id,
            'station_name' => 'SPKLU Aku (baru)',
            'session_at' => now(),
            'energy_kwh' => 5,
        ]);

        LogbookEntry::create([
            'user_id' => $otherUser->id,
            'station_name' => 'SPKLU Orang Lain',
            'session_at' => now()->subDays(2),
            'energy_kwh' => 20,
        ]);

        $response = $this->getJson('/api/v1/logbook-entries');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['station_name' => 'SPKLU Aku (baru)'])
            ->assertJsonMissing(['station_name' => 'SPKLU Orang Lain']);

        $this->assertEquals('SPKLU Aku (baru)', $response->json('data.0.station_name'));
    }

    public function test_it_creates_entry_with_station_snapshot(): void
    {
        $station = ChargingStation::create([
            'source' => 'esdm',
            'nama_lokasi' => 'SPKLU Kebon Jeruk',
            'alamat' => 'Jl. Kebon Jeruk No. 1',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'provider_name' => 'PLN',
            'type_charge' => 'Fast Charging',
        ]);

        $response = $this->postJson('/api/v1/logbook-entries', [
            'charging_station_id' => $station->id,
            'session_at' => '2026-08-04 10:00:00',
            'energy_kwh' => 25.5,
            'total_cost' => 85000,
            'parking_cost' => 5000,
            'notes' => 'Sesi malam',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'station_name' => 'SPKLU Kebon Jeruk',
                    'station_provider' => 'PLN',
                    'station_type_charge' => 'Fast Charging',
                    'energy_kwh' => 25.5,
                    'total_cost' => 85000,
                    'parking_cost' => 5000,
                ],
            ]);

        $this->assertDatabaseHas('logbook_entries', [
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
            'station_name' => 'SPKLU Kebon Jeruk',
        ]);
    }

    public function test_it_creates_manual_entry_without_station(): void
    {
        $response = $this->postJson('/api/v1/logbook-entries', [
            'station_name' => 'Charger Rumah',
            'session_at' => '2026-08-04 10:00:00',
            'energy_kwh' => 12,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['station_name' => 'Charger Rumah']);

        $this->assertDatabaseHas('logbook_entries', [
            'user_id' => $this->authUser->id,
            'station_name' => 'Charger Rumah',
        ]);
    }

    public function test_it_shows_entry(): void
    {
        $entry = LogbookEntry::create([
            'user_id' => $this->authUser->id,
            'station_name' => 'SPKLU Aku',
            'session_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/logbook-entries/{$entry->id}");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.station_name', 'SPKLU Aku');
    }

    public function test_it_forbids_access_to_other_users_entry(): void
    {
        $otherUser = User::factory()->create();
        $entry = LogbookEntry::create([
            'user_id' => $otherUser->id,
            'station_name' => 'SPKLU Rahasia',
            'session_at' => now(),
        ]);

        $this->getJson("/api/v1/logbook-entries/{$entry->id}")->assertForbidden();
        $this->putJson("/api/v1/logbook-entries/{$entry->id}", ['notes' => 'hack'])->assertForbidden();
        $this->deleteJson("/api/v1/logbook-entries/{$entry->id}")->assertForbidden();
    }

    public function test_it_updates_entry(): void
    {
        $entry = LogbookEntry::create([
            'user_id' => $this->authUser->id,
            'station_name' => 'SPKLU Aku',
            'session_at' => now(),
            'energy_kwh' => 10,
        ]);

        $response = $this->putJson("/api/v1/logbook-entries/{$entry->id}", [
            'energy_kwh' => 30,
            'notes' => 'Dua kali charge',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.energy_kwh', 30)
            ->assertJsonPath('data.notes', 'Dua kali charge');

        $this->assertDatabaseHas('logbook_entries', [
            'id' => $entry->id,
            'energy_kwh' => 30,
        ]);
    }

    public function test_it_deletes_entry(): void
    {
        $entry = LogbookEntry::create([
            'user_id' => $this->authUser->id,
            'station_name' => 'SPKLU Aku',
            'session_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/logbook-entries/{$entry->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('logbook_entries', ['id' => $entry->id]);
    }
}
