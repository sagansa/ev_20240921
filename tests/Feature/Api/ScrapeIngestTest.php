<?php

namespace Tests\Feature\Api;

use App\Models\Provider;
use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use App\Models\User;
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

        // Seed a few providers so DB-driven guessProvider can match in tests.
        Provider::create(['name' => 'PLN Mobile', 'status' => 1]);
        Provider::create(['name' => 'Shell', 'status' => 1]);
        Cache::forget('scrape:providers:'.Provider::count());
    }

    public function test_ingest_creates_raw_rows_with_chargers(): void
    {
        $response = $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => 'ChIJ-test-001',
                    'nama_lokasi' => 'SPKLU PLN UID Jakarta Pusat',
                    'alamat' => 'Jl. Medan Merdeka',
                    'latitude' => -6.1823,
                    'longitude' => 106.8295,
                    'rating' => 4.5,
                    'total_reviews' => 12,
                    'website' => 'https://pln.co.id',
                    'max_kw' => 120,
                    'total_charger' => 4,
                    'chargers' => [
                        ['connector_type' => 'CCS2', 'power_kw' => 120, 'jumlah_charger' => 2],
                        ['connector_type' => 'CHAdeMO', 'power_kw' => 50, 'jumlah_charger' => 2],
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.inserted', 1);

        $this->assertDatabaseCount('spklu_scrape_raw', 1);
        $this->assertDatabaseCount('spklu_scrape_raw_chargers', 2);

        $row = SpkluScrapeRaw::first();
        $this->assertEquals('SPKLU PLN UID Jakarta Pusat', $row->nama_lokasi);
        $this->assertEquals('PLN Mobile', $row->provider_name);
        $this->assertEquals('ultra_fast', $row->type_charge);
        $this->assertEquals(0, $row->status);
        $this->assertNotNull($row->dedup_hash);
        $this->assertSame('120 kW', $row->chargers()->first()->watt);
    }

    public function test_same_place_in_same_session_counts_as_duplicate(): void
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
        $response = $this->postJson('/api/v1/admin/scrape/ingest', $payload);

        $response->assertOk()->assertJsonPath('data.inserted', 0);
        $this->assertDatabaseCount('spklu_scrape_raw', 1);
    }

    public function test_non_super_admin_is_rejected(): void
    {
        $this->authUser->syncRoles([]);

        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => 'ChIJ-test-001',
                    'nama_lokasi' => 'SPKLU PLN UID Jakarta Pusat',
                    'latitude' => -6.1823,
                    'longitude' => 106.8295,
                ],
            ],
        ])->assertStatus(403);
    }

    public function test_provider_name_from_extension_is_preferred_over_guess(): void
    {
        // Name contains "PLN" (would guess "PLN Mobile"), but the extension
        // sends an explicit provider_name that must win.
        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => 'ChIJ-explicit-provider',
                    'nama_lokasi' => 'SPKLU PLN Random Place',
                    'latitude' => -6.5,
                    'longitude' => 106.8,
                    'provider_name' => 'Shell',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('spklu_scrape_raw', [
            'place_id' => 'ChIJ-explicit-provider',
            'provider_name' => 'Shell',
        ]);
    }

    public function test_ingested_place_id_is_stored_for_dedup(): void
    {
        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'places' => [
                [
                    'place_id' => '0x2e69f5:0xabcdef',
                    'nama_lokasi' => 'SPKLU Somewhere',
                    'latitude' => -6.7,
                    'longitude' => 107.0,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('spklu_scrape_raw', [
            'place_id' => '0x2e69f5:0xabcdef',
        ]);
    }

    public function test_re_scraping_approved_place_does_not_create_duplicate_production(): void
    {
        // First scrape: a brand new place, then approve it into production.
        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => '11111111-1111-1111-1111-111111111111',
            'places' => [
                [
                    'place_id' => '0xRESCRAPE:0x1',
                    'nama_lokasi' => 'SPKLU Recrape Test',
                    'latitude' => -6.9,
                    'longitude' => 107.5,
                    'max_kw' => 50,
                    'chargers' => [
                        ['connector_type' => 'CCS', 'power_kw' => 50, 'jumlah_charger' => 1],
                    ],
                ],
            ],
        ])->assertJsonPath('data.inserted', 1);

        $firstRow = SpkluScrapeRaw::firstWhere('place_id', '0xRESCRAPE:0x1');
        $location = app(SpkluScrapeMergeService::class)->approve($firstRow);
        $this->assertSame(1, SpkluLocation::where('place_id', '0xRESCRAPE:0x1')->count());

        // Second scrape in a DIFFERENT session, same place. Must be flagged as
        // duplicate of the production location, NOT inserted as new.
        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => '22222222-2222-2222-2222-222222222222',
            'places' => [
                [
                    'place_id' => '0xRESCRAPE:0x1',
                    'nama_lokasi' => 'SPKLU Recrape Test',
                    'latitude' => -6.9,
                    'longitude' => 107.5,
                ],
            ],
        ])->assertJsonPath('data.duplicates', 1)
          ->assertJsonPath('data.inserted', 0);

        // Production still has exactly one row for this place.
        $this->assertSame(1, SpkluLocation::where('place_id', '0xRESCRAPE:0x1')->count());

        // The new staging row knows which production location it duplicates.
        $dupes = SpkluScrapeRaw::where('place_id', '0xRESCRAPE:0x1')->where('status', SpkluScrapeRaw::STATUS_DUPLICATE)->get();
        $this->assertCount(1, $dupes);
        $this->assertSame($location->id, $dupes->first()->matched_spklu_location_id);
    }

    public function test_fuzzy_name_match_flags_duplicate_without_place_id(): void
    {
        // Production location WITHOUT place_id (e.g. legacy JSON-imported data).
        $existing = SpkluLocation::create([
            'external_id' => 9001,
            'provinsi' => 'DKI Jakarta',
            'nama_lokasi' => 'SPKLU PLN Tebet Barat',
            'latitude' => -6.2345,
            'longitude' => 106.8550,
        ]);

        // Scrape a place with slightly different name + no place_id, within
        // ~200m. Must be flagged as a fuzzy duplicate, not inserted as new.
        $this->postJson('/api/v1/admin/scrape/ingest', [
            'session' => '33333333-3333-3333-3333-333333333333',
            'places' => [
                [
                    'nama_lokasi' => 'SPKLU PLN Tebet Barat Depot',
                    'latitude' => -6.2346,
                    'longitude' => 106.8551,
                ],
            ],
        ])->assertJsonPath('data.duplicates', 1)
          ->assertJsonPath('data.inserted', 0);

        $row = SpkluScrapeRaw::firstWhere('nama_lokasi', 'SPKLU PLN Tebet Barat Depot');
        $this->assertSame(SpkluScrapeRaw::STATUS_DUPLICATE, $row->status);
        $this->assertSame($existing->id, $row->matched_spklu_location_id);
        $this->assertSame(1, SpkluLocation::count());
    }
}
