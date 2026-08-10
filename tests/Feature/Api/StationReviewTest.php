<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\ChargingStation;
use App\Models\StationReview;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StationReviewTest extends ApiTestCase
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

    private function makeReview(ChargingStation $station, int $rating, string $comment = 'Bagus', ?int $userId = null): StationReview
    {
        return StationReview::create([
            'charging_station_id' => $station->id,
            'user_id' => $userId ?? $this->authUser->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_anonymous' => true,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Publik: list & summary
    // ─────────────────────────────────────────────────────────────────────

    public function test_guest_can_list_reviews(): void
    {
        $station = $this->makeStation();
        $this->makeReview($station, 5, 'Pertama');
        $this->makeReview($station, 4, 'Kedua');

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/reviews");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'rating', 'comment', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'has_more'],
            ]);
    }

    public function test_guest_list_is_paginated_and_anonymous(): void
    {
        $station = $this->makeStation();
        $this->makeReview($station, 5, 'Baru');

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/reviews?per_page=1");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1);
        // Anonim: tidak ada field user / user_id di resource.
        $response->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.user');
    }

    public function test_guest_can_get_summary(): void
    {
        $station = $this->makeStation();
        $this->makeReview($station, 5);
        $this->makeReview($station, 5);
        $this->makeReview($station, 4);
        $this->makeReview($station, 3);
        $this->makeReview($station, 1);

        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/stations/{$station->id}/reviews/summary");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating_avg' => 3.6, // (5+5+4+3+1)/5 = 18/5 = 3.6
                    'rating_count' => 5,
                    'distribution' => [1 => 1, 2 => 0, 3 => 1, 4 => 1, 5 => 2],
                ],
            ]);
    }

    public function test_summary_empty_station_returns_zeroes(): void
    {
        $station = $this->makeStation();

        $this->app['auth']->forgetGuards();

        $this->getJson("/api/v1/stations/{$station->id}/reviews/summary")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'rating_avg' => 0.0,
                    'rating_count' => 0,
                    'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Eligibility
    // ─────────────────────────────────────────────────────────────────────

    public function test_eligibility_true_after_completed_charge(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $this->getJson("/api/v1/stations/{$station->id}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.is_eligible', true)
            ->assertJsonPath('data.reason', null);
    }

    public function test_eligibility_false_without_completed_charge(): void
    {
        $station = $this->makeStation();
        // Sesi belum selesai (is_finish_charging = false) → tidak eligible.
        Charge::create([
            'user_id' => $this->authUser->id,
            'charging_station_id' => $station->id,
            'date' => now()->toDateString(),
            'is_finish_charging' => false,
        ]);

        $this->getJson("/api/v1/stations/{$station->id}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.is_eligible', false)
            ->assertJsonPath('data.reason', 'Kamu belum pernah menyelesaikan sesi charging di lokasi ini.');
    }

    public function test_eligibility_false_for_non_pln_station(): void
    {
        $station = $this->makeStation(['source' => 'esdm']);
        $this->makeCompletedCharge($station);

        $this->getJson("/api/v1/stations/{$station->id}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.is_eligible', false)
            ->assertJsonPath('data.reason', 'Ulasan hanya bisa dibuat untuk SPKLU PLN.');
    }

    public function test_eligibility_requires_auth(): void
    {
        $station = $this->makeStation();

        $this->app['auth']->forgetGuards();

        try {
            $this->getJson("/api/v1/stations/{$station->id}/reviews/eligibility");
            $this->fail('Expected AuthenticationException for guest.');
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $this->assertSame('Unauthenticated.', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────────────────────────────────

    public function test_store_forbidden_without_completed_charge(): void
    {
        $station = $this->makeStation();

        $this->postJson("/api/v1/stations/{$station->id}/reviews", [
            'rating' => 5,
            'comment' => 'Nice',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Kamu belum pernah menyelesaikan sesi charging di lokasi ini.');
    }

    public function test_store_forbidden_for_non_pln_station(): void
    {
        $station = $this->makeStation(['source' => 'esdm']);
        $this->makeCompletedCharge($station);

        $this->postJson("/api/v1/stations/{$station->id}/reviews", [
            'rating' => 5,
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Ulasan hanya bisa dibuat untuk SPKLU PLN.');
    }

    public function test_store_creates_anonymous_review(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $response = $this->postJson("/api/v1/stations/{$station->id}/reviews", [
            'rating' => 5,
            'comment' => 'SPKLU bagus, bersih, cepet',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating' => 5,
                    'comment' => 'SPKLU bagus, bersih, cepet',
                ],
            ]);

        // Resource anonim — user_id tidak diekspos.
        $response->assertJsonMissingPath('data.user_id');

        $this->assertDatabaseHas('station_reviews', [
            'charging_station_id' => $station->id,
            'user_id' => $this->authUser->id,
            'rating' => 5,
            'is_anonymous' => true,
        ]);
    }

    public function test_multiple_reviews_same_user_allowed(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        $this->postJson("/api/v1/stations/{$station->id}/reviews", ['rating' => 5])->assertStatus(201);
        $this->postJson("/api/v1/stations/{$station->id}/reviews", ['rating' => 3])->assertStatus(201);

        $this->assertSame(2, StationReview::where('charging_station_id', $station->id)->count());
    }

    public function test_store_rating_validation_between_1_and_5(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        try {
            $this->postJson("/api/v1/stations/{$station->id}/reviews", ['rating' => 0]);
            $this->fail('Expected ValidationException for rating 0.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rating', $e->errors());
        }

        try {
            $this->postJson("/api/v1/stations/{$station->id}/reviews", ['rating' => 6]);
            $this->fail('Expected ValidationException for rating 6.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rating', $e->errors());
        }
    }

    public function test_store_comment_max_2000(): void
    {
        $station = $this->makeStation();
        $this->makeCompletedCharge($station);

        try {
            $this->postJson("/api/v1/stations/{$station->id}/reviews", [
                'rating' => 4,
                'comment' => str_repeat('a', 2001),
            ]);
            $this->fail('Expected ValidationException for comment > 2000.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('comment', $e->errors());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Delete (admin only)
    // ─────────────────────────────────────────────────────────────────────

    public function test_delete_forbidden_for_non_admin(): void
    {
        $station = $this->makeStation();
        $review = $this->makeReview($station, 5);

        $this->deleteJson("/api/v1/stations/{$station->id}/reviews/{$review->id}")
            ->assertStatus(403);

        $this->assertNotSoftDeleted('station_reviews', ['id' => $review->id]);
    }

    public function test_admin_can_delete_review(): void
    {
        Role::findOrCreate('admin', 'web');
        $this->authUser->assignRole('admin');

        $station = $this->makeStation();
        $review = $this->makeReview($station, 5);

        $this->deleteJson("/api/v1/stations/{$station->id}/reviews/{$review->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('station_reviews', ['id' => $review->id]);
    }
}
