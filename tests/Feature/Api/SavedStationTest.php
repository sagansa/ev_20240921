<?php

namespace Tests\Feature\Api;

use App\Models\ChargingStation;
use App\Models\SavedStation;

class SavedStationTest extends ApiTestCase
{
    private function makeStation(array $overrides = []): ChargingStation
    {
        return ChargingStation::create(array_merge([
            'source' => 'pln',
            'nama_lokasi' => 'SPKLU PLN Senayan',
            'alamat' => 'Jl. Asia Afrika',
            'latitude' => -6.22,
            'longitude' => 106.80,
            'provider_name' => 'PLN',
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Toggle save/unsaved
    // ─────────────────────────────────────────────────────────────────────

    public function test_toggle_saves_station_when_not_saved(): void
    {
        $station = $this->makeStation();

        $this->postJson("/api/v1/stations/{$station->id}/save")
            ->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['is_saved' => true]]);

        $this->assertDatabaseHas('saved_stations', [
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);
    }

    public function test_toggle_unsaves_when_already_saved(): void
    {
        $station = $this->makeStation();
        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);

        $this->postJson("/api/v1/stations/{$station->id}/save")
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['is_saved' => false]]);

        $this->assertDatabaseMissing('saved_stations', [
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);
    }

    public function test_toggle_works_for_non_pln_station(): void
    {
        // Bookmark tidak gated source — ESDM juga bisa.
        $station = $this->makeStation(['source' => 'esdm']);

        $this->postJson("/api/v1/stations/{$station->id}/save")
            ->assertStatus(201)
            ->assertJson(['data' => ['is_saved' => true]]);
    }

    public function test_toggle_requires_auth(): void
    {
        $station = $this->makeStation();

        $this->app['auth']->forgetGuards();

        try {
            $this->postJson("/api/v1/stations/{$station->id}/save");
            $this->fail('Expected AuthenticationException for guest.');
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $this->assertSame('Unauthenticated.', $e->getMessage());
        }
    }

    public function test_toggle_is_user_scoped(): void
    {
        // Save oleh user A tidak mempengaruhi user B.
        $otherUser = \App\Models\User::factory()->create();
        $station = $this->makeStation();
        SavedStation::create([
            'user_id' => $otherUser->id,
            'charging_station_id' => $station->id,
        ]);

        // User saat ini (authUser) toggle → save (karena dia belum simpan).
        $this->postJson("/api/v1/stations/{$station->id}/save")
            ->assertStatus(201);

        // Bookmark user B tetap ada.
        $this->assertDatabaseHas('saved_stations', [
            'user_id' => $otherUser->id,
            'charging_station_id' => $station->id,
        ]);
    }

    public function test_unique_constraint_one_bookmark_per_user_per_station(): void
    {
        $station = $this->makeStation();

        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);

        // Coba insert langsung duplikat → harus throw (unique constraint).
        $this->expectException(\Illuminate\Database\QueryException::class);
        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Check
    // ─────────────────────────────────────────────────────────────────────

    public function test_check_returns_false_when_not_saved(): void
    {
        $station = $this->makeStation();

        $this->getJson("/api/v1/stations/{$station->id}/save")
            ->assertOk()
            ->assertJson(['data' => ['is_saved' => false]]);
    }

    public function test_check_returns_true_when_saved(): void
    {
        $station = $this->makeStation();
        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);

        $this->getJson("/api/v1/stations/{$station->id}/save")
            ->assertOk()
            ->assertJson(['data' => ['is_saved' => true]]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Index (list saved stations)
    // ─────────────────────────────────────────────────────────────────────

    public function test_index_returns_only_my_saved_stations(): void
    {
        $mine = $this->makeStation(['nama_lokasi' => 'SPKLU Saya']);
        $other = $this->makeStation(['nama_lokasi' => 'SPKLU Orang']);

        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $mine->id,
        ]);

        $otherUser = \App\Models\User::factory()->create();
        SavedStation::create([
            'user_id' => $otherUser->id,
            'charging_station_id' => $other->id,
        ]);

        $response = $this->getJson('/api/v1/me/saved-stations');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_lokasi', 'SPKLU Saya');
    }

    public function test_index_returns_spklu_shape(): void
    {
        // List harus sama shape dgn GET /spklu (id, nama_lokasi, lat, lng, dll)
        // agar mobile bisa render pin langsung tanpa transformasi.
        $station = $this->makeStation();
        SavedStation::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
        ]);

        $response = $this->getJson('/api/v1/me/saved-stations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'nama_lokasi', 'latitude', 'longitude']],
            ]);
    }

    public function test_index_empty_when_no_bookmarks(): void
    {
        $this->getJson('/api/v1/me/saved-stations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
