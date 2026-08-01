<?php

namespace Tests\Feature\Api;

use App\Models\SpkluScrapeRaw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
