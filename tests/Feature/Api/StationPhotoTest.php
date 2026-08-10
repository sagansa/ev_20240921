<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\ChargingStation;
use App\Models\StationPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StationPhotoTest extends ApiTestCase
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

    private function makeCompletedCharge(ChargingStation $station, ?int $userId = null): Charge
    {
        return Charge::create([
            'user_id' => $userId ?? $this->authUser->id,
            'charging_station_id' => $station->id,
            'station_name_snapshot' => $station->nama_lokasi,
            'date' => now()->toDateString(),
            'kWh' => 20.0,
            'total_cost' => 60000,
            'is_finish_charging' => true,
        ]);
    }

    private function makePhoto(ChargingStation $station, string $path = 'station-photos/1/fake.jpg', ?int $userId = null): StationPhoto
    {
        return StationPhoto::create([
            'charging_station_id' => $station->id,
            'user_id' => $userId ?? $this->authUser->id,
            'path' => $path,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Fake disk `public` agar file upload tidak benar-benar tertulis ke disk.
        Storage::fake('public');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Publik: list
    // ─────────────────────────────────────────────────────────────────────

    public function test_guest_can_list_photos(): void
    {
        $station = $this->makeStation();
        $this->makePhoto($station, 'station-photos/1/a.jpg');
        $this->makePhoto($station, 'station-photos/1/b.jpg');

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/photos");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'url', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'has_more'],
            ]);
    }

    public function test_guest_list_is_anonymous_and_url_resolved(): void
    {
        $station = $this->makeStation();
        $this->makePhoto($station, 'station-photos/42/abc.jpg');

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/photos");

        $response->assertOk()
            ->assertJsonPath('data.0.url', '/storage/station-photos/42/abc.jpg');
        // Anonim: tidak ada field user / user_id.
        $response->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.user');
    }

    public function test_list_is_paginated(): void
    {
        $station = $this->makeStation();
        for ($i = 0; $i < 5; $i++) {
            $this->makePhoto($station, "station-photos/1/$i.jpg");
        }

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/photos?per_page=2");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.has_more', true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Upload (store)
    // ─────────────────────────────────────────────────────────────────────

    public function test_store_forbidden_without_completed_charge(): void
    {
        $station = $this->makeStation();

        $this->postJson("/api/v1/stations/{$station->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Kamu belum pernah menyelesaikan sesi charging di lokasi ini.');
    }

    public function test_store_forbidden_for_non_pln_station(): void
    {
        $station = $this->makeStation(['source' => 'esdm']);
        $this->makeCompletedCharge($station);

        $this->postJson("/api/v1/stations/{$station->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Foto hanya bisa diunggah untuk SPKLU PLN.');
    }

    public function test_store_uploads_multiple_photos(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $response = $this->postJson("/api/v1/stations/{$station->id}/photos", [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.png'),
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');

        // File tersimpan di disk `public` di subdir station-photos/{id}/.
        $this->assertSame(2, StationPhoto::where('charging_station_id', $station->id)->count());

        $photo = StationPhoto::first();
        $this->assertStringStartsWith("station-photos/{$station->id}/", $photo->path);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_store_creates_anonymous_photo_records(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $response = $this->postJson("/api/v1/stations/{$station->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ]);

        // Resource anonim — user_id tidak diekspos.
        $response->assertJsonMissingPath('data.0.user_id');

        // Tapi user_id tersimpan di DB (utk gate/moderasi).
        $this->assertDatabaseHas('station_photos', [
            'charging_station_id' => $station->id,
            'user_id' => $this->authUser->id,
        ]);
    }

    public function test_store_rejects_more_than_5_photos(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $payload = ['photos' => []];
        for ($i = 0; $i < 6; $i++) {
            $payload['photos'][] = UploadedFile::fake()->image("photo$i.jpg");
        }

        try {
            $this->postJson("/api/v1/stations/{$station->id}/photos", $payload);
            $this->fail('Expected ValidationException for >5 photos.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('photos', $e->errors());
        }
    }

    public function test_store_rejects_non_image_file(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        try {
            $this->postJson("/api/v1/stations/{$station->id}/photos", [
                'photos' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
            ]);
            $this->fail('Expected ValidationException for non-image file.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('photos.0', $e->errors());
        }
    }

    public function test_store_requires_at_least_one_photo(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        try {
            $this->postJson("/api/v1/stations/{$station->id}/photos", [
                'photos' => [],
            ]);
            $this->fail('Expected ValidationException for empty photos array.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('photos', $e->errors());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Delete (admin only)
    // ─────────────────────────────────────────────────────────────────────

    public function test_delete_forbidden_for_non_admin(): void
    {
        $station = $this->makeStation();
        $photo = $this->makePhoto($station);

        $this->deleteJson("/api/v1/stations/{$station->id}/photos/{$photo->id}")
            ->assertStatus(403);

        $this->assertNotSoftDeleted('station_photos', ['id' => $photo->id]);
    }

    public function test_admin_can_delete_photo_and_removes_file(): void
    {
        Role::findOrCreate('admin', 'web');
        $this->authUser->assignRole('admin');

        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        // Upload beneran agar file ada di disk fake.
        $this->postJson("/api/v1/stations/{$station->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertStatus(201);

        $photo = StationPhoto::first();
        Storage::disk('public')->assertExists($photo->path);

        $this->deleteJson("/api/v1/stations/{$station->id}/photos/{$photo->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('station_photos', ['id' => $photo->id]);
        // File fisik ikut terhapus.
        Storage::disk('public')->assertMissing($photo->path);
    }
}
