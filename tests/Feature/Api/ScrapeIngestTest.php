<?php

namespace Tests\Feature\Api;

use App\Models\Provider;
use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use App\Models\User;
use App\Services\ScrapeDedupService;
use App\Services\SpkluScrapeMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScrapeIngestTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create(['email' => 'admin@admin.com']);
        Role::findOrCreate('super_admin', 'web');
        $this->authUser->assignRole('super_admin');
        Sanctum::actingAs($this->authUser, abilities: ['*']);
    }

    public function test_ingest_creates_staging_rows_with_chargers_and_does_not_touch_production(): void
    {
        Provider::create(['name' => 'PLN Mobile', 'status' => 1]);
        Cache::forget('scrape:providers:'.Provider::count());

        $response = $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => 'ChIJ-test-001',
                    'nama_lokasi' => 'SPKLU PLN UID Jakarta Pusat',
                    'alamat' => 'Jl. Medan Merdeka',
                    'latitude' => -6.1823,
                    'longitude' => 106.8295,
                    'max_kw' => 120,
                    'chargers' => [
                        ['connector_type' => 'CCS2', 'power_kw' => 120, 'jumlah_charger' => 2],
                        ['connector_type' => 'CHAdeMO', 'power_kw' => 50, 'jumlah_charger' => 2],
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.inserted', 1);

        // Staging row exists with chargers and defaults to NEW.
        $this->assertDatabaseCount('spklu_scrape_raw', 1);
        $this->assertDatabaseCount('spklu_scrape_raw_chargers', 2);
        $row = SpkluScrapeRaw::first();
        $this->assertSame(SpkluScrapeRaw::STATUS_NEW, $row->status);
        $this->assertSame('PLN Mobile', $row->provider_name);

        // CRITICAL: production is untouched by the scrape pipeline.
        $this->assertSame(0, SpkluLocation::count());
    }

    public function test_same_place_in_same_session_is_not_re_inserted(): void
    {
        $payload = [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => 'ChIJ-test-001',
                    'nama_lokasi' => 'SPKLU PLN UID Jakarta Pusat',
                    'latitude' => -6.1823,
                    'longitude' => 106.8295,
                ],
            ],
        ];

        $this->postJson('/api/v1/admin/scrape/ingest', $payload)->assertJsonPath('data.inserted', 1);
        $second = $this->postJson('/api/v1/admin/scrape/ingest', $payload);

        // Within a session, the same (place_id|dedup_hash) is de-duplicated at
        // the staging level so the second POST does not create another row.
        $second->assertOk()->assertJsonPath('data.inserted', 0);
        $this->assertDatabaseCount('spklu_scrape_raw', 1);
        $this->assertSame(0, SpkluLocation::count());
    }

    public function test_non_super_admin_is_rejected(): void
    {
        $this->authUser->syncRoles([]);

        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                ['nama_lokasi' => 'X', 'latitude' => -6.2, 'longitude' => 106.8],
            ],
        ])->assertStatus(403);
    }

    public function test_mark_approved_does_not_insert_into_production(): void
    {
        $row = SpkluScrapeRaw::create([
            'nama_lokasi' => 'SPKLU Approved Sample',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'dedup_hash' => sha1('x'),
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);

        app(SpkluScrapeMergeService::class)->markApproved($row);

        $this->assertSame(SpkluScrapeRaw::STATUS_APPROVED, $row->fresh()->status);
        $this->assertSame(0, SpkluLocation::count(), 'production must remain untouched');
    }

    public function test_mark_approved_with_link_stores_reference_only(): void
    {
        $existing = SpkluLocation::create([
            'external_id' => 1, 'provinsi' => 'X', 'nama_lokasi' => 'Existing',
        ]);
        $row = SpkluScrapeRaw::create([
            'nama_lokasi' => 'SPKLU Link Sample',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'dedup_hash' => sha1('y'),
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);

        app(SpkluScrapeMergeService::class)->markApproved($row, $existing->id);

        $this->assertSame($existing->id, $row->fresh()->linked_spklu_location_id);
        // Production row unchanged.
        $this->assertSame(1, SpkluLocation::count());
        $this->assertSame('Existing', $existing->fresh()->nama_lokasi);
    }

    public function test_recommend_candidates_returns_ranked_suggestions(): void
    {
        Cache::flush();
        $a = SpkluLocation::create(['external_id' => 1, 'provinsi' => 'X', 'nama_lokasi' => 'SPKLU PLN Tebet Barat', 'latitude' => -6.2345, 'longitude' => 106.8550]);
        SpkluLocation::create(['external_id' => 2, 'provinsi' => 'X', 'nama_lokasi' => 'SPKLU Acme Far Away', 'latitude' => -6.9, 'longitude' => 107.5]);

        $row = SpkluScrapeRaw::create([
            'nama_lokasi' => 'SPKLU PLN Tebet Barat Depot',
            'latitude' => -6.2346,
            'longitude' => 106.8551,
            'dedup_hash' => sha1('z'),
            'scrape_session' => 's',
        ]);

        $candidates = app(ScrapeDedupService::class)->recommendCandidates($row, 5);

        $this->assertNotEmpty($candidates);
        $this->assertSame($a->id, $candidates[0]['id'], 'closest/most-similar first');
        $this->assertGreaterThan(0, $candidates[0]['similarity_pct']);
        $this->assertLessThan(1, $candidates[0]['distance_km']);
    }

    /**
     * Layar display UNION scrape sudah DIHAPUS dari /api/v1/spklu (Phase 4
     * canonical layer). Approved scrape rows TIDAK lagi di-serving langsung —
     * serving murni dari charging_stations (kanonik).
     */
    public function test_api_index_no_longer_serves_scrape_rows(): void
    {
        SpkluScrapeRaw::create([
            'nama_lokasi' => 'Scrape Visible', 'latitude' => -6.3, 'longitude' => 106.9,
            'dedup_hash' => sha1('a'), 'scrape_session' => 's', 'status' => SpkluScrapeRaw::STATUS_APPROVED,
        ]);
        SpkluScrapeRaw::create([
            'nama_lokasi' => 'Scrape Hidden', 'latitude' => -6.4, 'longitude' => 107.0,
            'dedup_hash' => sha1('b'), 'scrape_session' => 's', 'status' => SpkluScrapeRaw::STATUS_NEW,
        ]);

        $resp = $this->getJson('/api/v1/spklu?per_page=500');
        $resp->assertOk();

        $names = collect($resp->json('data'))->pluck('nama_lokasi');
        $this->assertNotContains('Scrape Visible', $names);
        $this->assertNotContains('Scrape Hidden', $names);
    }

    /**
     * include_scrape kini diabaikan sepenuhnya — scrape tidak pernah masuk ke
     * serving (baik flag on maupun off).
     */
    public function test_api_index_ignores_include_scrape_flag(): void
    {
        SpkluScrapeRaw::create([
            'nama_lokasi' => 'Scrape Visible', 'latitude' => -6.3, 'longitude' => 106.9,
            'dedup_hash' => sha1('a'), 'scrape_session' => 's', 'status' => SpkluScrapeRaw::STATUS_APPROVED,
        ]);

        $resp = $this->getJson('/api/v1/spklu?per_page=500&include_scrape=1');
        $names = collect($resp->json('data'))->pluck('nama_lokasi');
        $this->assertNotContains('Scrape Visible', $names);
    }
}
