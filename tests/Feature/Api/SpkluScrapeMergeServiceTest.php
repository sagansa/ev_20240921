<?php

namespace Tests\Feature\Api;

use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use App\Services\SpkluScrapeMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpkluScrapeMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'SPKLU, Jakarta',
                'address' => [
                    'state' => 'DKI Jakarta',
                    'city' => 'Jakarta Pusat',
                ],
                'place_id' => 123,
            ]),
        ]);
    }

    private function makeStagedRow(array $overrides = []): SpkluScrapeRaw
    {
        $row = SpkluScrapeRaw::create(array_merge([
            'place_id' => 'ChIJ-test',
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'max_kw' => 120,
            'type_charge' => 'ultrafast',
            'total_charger' => 4,
            'total_konektor' => 2,
            'dedup_hash' => sha1('TEST'),
            'status' => 0,
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ], $overrides));

        $row->chargers()->create([
            'connector_type' => 'CCS2',
            'power_kw' => 120,
            'watt' => '120 kW',
            'type_charge' => 'ultrafast',
            'jumlah_charger' => 2,
        ]);

        return $row;
    }

    public function test_approve_merges_row_into_production(): void
    {
        $row = $this->makeStagedRow();
        $service = app(SpkluScrapeMergeService::class);

        $location = $service->approve($row);

        $this->assertInstanceOf(SpkluLocation::class, $location);
        $this->assertSame(1, $location->status);
        $this->assertSame('SPKLU PLN Jakarta', $location->nama_lokasi);
        $this->assertSame('DKI JAKARTA', $location->provinsi);
        $this->assertSame('Jakarta Pusat', $location->kabupaten_kota);

        $this->assertDatabaseCount('spklu_locations', 1);
        $this->assertDatabaseCount('spklu_charger_boxes', 1);

        $row->refresh();
        $this->assertSame(2, $row->status);
        $this->assertSame($location->id, $row->matched_spklu_location_id);
    }

    public function test_approve_generates_incremental_external_id(): void
    {
        SpkluLocation::create([
            'external_id' => 500,
            'provinsi' => 'DKI Jakarta',
            'nama_lokasi' => 'Existing',
        ]);

        $row = $this->makeStagedRow();
        $location = app(SpkluScrapeMergeService::class)->approve($row);

        $this->assertSame(501, $location->external_id);
    }

    public function test_approve_respects_overrides(): void
    {
        $row = $this->makeStagedRow();
        $location = app(SpkluScrapeMergeService::class)->approve($row, [
            'provinsi' => 'JAWA BARAT',
            'kabupaten_kota' => 'Bandung',
            'type_charge' => 'fast',
        ]);

        $this->assertSame('JAWA BARAT', $location->provinsi);
        $this->assertSame('Bandung', $location->kabupaten_kota);
        $this->assertSame('fast', $location->type_charge);
    }

    public function test_approve_is_idempotent_when_already_approved(): void
    {
        $row = $this->makeStagedRow();
        $service = app(SpkluScrapeMergeService::class);

        $first = $service->approve($row);
        $second = $service->approve($row->refresh());

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('spklu_locations', 1);
    }

    public function test_reject_marks_status_as_rejected(): void
    {
        $row = $this->makeStagedRow();
        app(SpkluScrapeMergeService::class)->reject($row);

        $this->assertSame(3, $row->fresh()->status);
        $this->assertDatabaseCount('spklu_locations', 0);
    }

    public function test_approve_duplicate_updates_existing_without_inserting_new(): void
    {
        // Existing production location already matched to the staged row.
        $existing = SpkluLocation::create([
            'external_id' => 100,
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'provinsi' => 'DKI Jakarta',
            'type_charge' => null,   // empty -> should be backfilled
            'watt' => null,          // empty -> should be backfilled
            'total_charger' => 0,
        ]);

        $row = $this->makeStagedRow([
            'status' => SpkluScrapeRaw::STATUS_DUPLICATE,
            'matched_spklu_location_id' => $existing->id,
        ]);

        $location = app(SpkluScrapeMergeService::class)->approve($row);

        // No new location inserted, the existing one is reused.
        $this->assertSame($existing->id, $location->id);
        $this->assertDatabaseCount('spklu_locations', 1);

        // Empty fields are backfilled from the scrape.
        $this->assertSame('ultrafast', $location->fresh()->type_charge);
        $this->assertSame('120 kW', $location->fresh()->watt);

        // Staging row marked approved.
        $this->assertSame(2, $row->fresh()->status);
    }

    public function test_approve_duplicate_does_not_overwrite_manual_edits(): void
    {
        $existing = SpkluLocation::create([
            'external_id' => 100,
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'provinsi' => 'JAWA BARAT',     // manually corrected
            'type_charge' => 'fast',        // manually set
            'watt' => '50 kW',              // manually set
        ]);

        $row = $this->makeStagedRow([
            'status' => SpkluScrapeRaw::STATUS_DUPLICATE,
            'matched_spklu_location_id' => $existing->id,
        ]);

        $location = app(SpkluScrapeMergeService::class)->approve($row);

        // Pre-existing values are preserved (not overwritten by scrape).
        $this->assertSame('JAWA BARAT', $location->fresh()->provinsi);
        $this->assertSame('fast', $location->fresh()->type_charge);
        $this->assertSame('50 kW', $location->fresh()->watt);
    }

    public function test_approve_duplicate_appends_only_missing_chargers(): void
    {
        $existing = SpkluLocation::create([
            'external_id' => 100,
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'provinsi' => 'DKI Jakarta',
        ]);
        // A legacy JSON-imported charger (model name, no connector info, like
        // the real data). Same watt as the scrape -> must NOT be duplicated.
        $existing->chargerBoxes()->create([
            'nama_chargerbox' => 'CS Energy DC 120kW',
            'watt' => '120 kW',
            'jumlah_charger' => 2,
        ]);

        $row = $this->makeStagedRow([
            'status' => SpkluScrapeRaw::STATUS_DUPLICATE,
            'matched_spklu_location_id' => $existing->id,
        ]);
        // An additional connector the production location doesn't have yet.
        $row->chargers()->create([
            'connector_type' => 'CHAdeMO',
            'power_kw' => 50,
            'watt' => '50 kW',
            'type_charge' => 'fast',
            'jumlah_charger' => 1,
        ]);

        app(SpkluScrapeMergeService::class)->approve($row);

        // 1 pre-existing (same 120 kW watt, deduped) + 1 newly appended.
        $this->assertSame(2, $existing->fresh()->chargerBoxes()->count());
    }
}
