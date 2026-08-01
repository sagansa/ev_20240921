<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Panel\SpkluScrapeRawResource;
use App\Filament\Resources\Panel\SpkluScrapeRawResource\Pages\ListSpkluScrapeRaws;
use App\Models\SpkluScrapeRaw;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpkluScrapeRawResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        $this->admin = User::factory()->create(['email' => 'admin@admin.com']);
        $this->admin->assignRole('super_admin');
    }

    public function test_list_page_renders_with_rows(): void
    {
        SpkluScrapeRaw::create([
            'place_id' => 'ChIJ-test',
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'dedup_hash' => sha1('X'),
            'status' => 0,
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ListSpkluScrapeRaws::class);

        $component->assertSuccessful()
            ->assertCanSeeTableRecords(SpkluScrapeRaw::all());
    }

    public function test_non_super_admin_cannot_access_resource(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(SpkluScrapeRawResource::canViewAny($user));
    }

    public function test_approve_action_merges_row_to_production(): void
    {
        $row = SpkluScrapeRaw::create([
            'place_id' => 'ChIJ-test',
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'max_kw' => 120,
            'type_charge' => 'ultra_fast',
            'dedup_hash' => sha1('Y'),
            'status' => 0,
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(ListSpkluScrapeRaws::class);

        $component->callAction(TestAction::make('approve')->table($row), [
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'provinsi' => 'DKI Jakarta',
            'kabupaten_kota' => 'Jakarta Pusat',
            'type_charge' => 'ultra_fast',
            'watt' => '120 kW',
        ])->assertNotified();

        $this->assertDatabaseCount('spklu_locations', 1);
        $this->assertDatabaseHas('spklu_locations', [
            'nama_lokasi' => 'SPKLU PLN Jakarta',
            'provinsi' => 'DKI Jakarta',
            'kabupaten_kota' => 'Jakarta Pusat',
        ]);

        $row->refresh();
        $this->assertSame(2, $row->status);
        $this->assertNotNull($row->matched_spklu_location_id);
    }

    public function test_reject_action_marks_row_as_rejected(): void
    {
        $row = SpkluScrapeRaw::create([
            'place_id' => 'ChIJ-test',
            'nama_lokasi' => 'Data Sampah',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'dedup_hash' => sha1('Z'),
            'status' => 0,
            'scrape_session' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListSpkluScrapeRaws::class)
            ->callAction(TestAction::make('reject')->table($row))
            ->assertNotified();

        $this->assertSame(3, $row->fresh()->status);
        $this->assertDatabaseCount('spklu_locations', 0);
    }
}
