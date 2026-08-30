<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\VehicleNameMapping;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VehicleNameMappingTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): void
    {
        $wuling = BrandVehicle::create(['name' => 'Wuling']);
        ModelVehicle::create(['name' => 'Air EV', 'brand_vehicle_id' => $wuling->id, 'powertrain' => 'BEV']);
    }

    private function writeCsv(array $rows): string
    {
        $path = storage_path('app/mapping-test-'.uniqid().'.csv');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['RAW_BRAND', 'RAW_MODEL', 'BRAND_VEHICLE', 'MODEL_VEHICLE', 'TYPE_VEHICLE', 'CATATAN']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }

    public function test_record_valid_dan_idempoten(): void
    {
        $this->seedCatalog();

        $first = VehicleNameMapping::record('WULING-DBG', 'Air EV Baru', 'Wuling', 'Air EV', null, 'data hantu');
        $second = VehicleNameMapping::record('wuling-dbg', 'air EV BARU', 'Wuling', 'Air EV');

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id); // kunci ternormalisasi sama
        $this->assertSame(1, VehicleNameMapping::count());
    }

    public function test_record_gagal_bila_katalog_belum_ada(): void
    {
        $this->seedCatalog();

        $this->assertNull(VehicleNameMapping::record('X', 'Y', 'Brand Tak Ada', 'Air EV'));
        $this->assertNull(VehicleNameMapping::record('X', 'Y', 'Wuling', 'Model Tak Ada'));
        $this->assertSame(0, VehicleNameMapping::count());
    }

    public function test_import_csv_dan_tolak_yang_katalognya_tidak_ada(): void
    {
        $this->seedCatalog();
        $csv = $this->writeCsv([
            ['WULING-DBG', 'Air EV Baru', 'Wuling', 'Air EV', '', 'gabung ke Wuling'],
            ['HINO', 'EV Truck X', 'HINO', 'EV Truck X', '', ''],
        ]);

        $this->artisan('vehicle-mapping', ['action' => 'import', '--csv' => $csv])
            ->expectsOutputToContain('Mapping tersimpan: 1')
            ->expectsOutputToContain('Gagal (katalog belum ada): 1')
            ->assertFailed();

        $this->assertSame(1, VehicleNameMapping::count());
    }

    public function test_relink_memperbarui_stats_null_link(): void
    {
        $this->seedCatalog();
        VehicleNameMapping::record('WULING-DBG', 'Air EV Baru', 'Wuling', 'Air EV');

        $import = \App\Models\SalesImport::create([
            'file_name' => '2026-01.csv', 'source' => 'gaikindo', 'year' => 2026, 'status' => 'processed',
        ]);
        $stat = VehicleSalesStat::create([
            'sales_import_id' => $import->id, 'raw_brand' => 'Wuling-dbg', 'raw_model' => 'AIR EV BARU',
            'powertrain' => 'BEV', 'year' => 2026, 'month' => 1, 'units' => 3,
        ]);

        $this->artisan('vehicle-mapping', ['action' => 'relink'])
            ->expectsOutputToContain('Baris stats ter-relink: 1')
            ->assertSuccessful();

        $stat->refresh();
        $this->assertSame('Wuling', $stat->brandVehicle->name);
        $this->assertSame('Air EV', $stat->modelVehicle->name);
    }

    public function test_gui_simpan_mapping_dari_halaman_preview(): void
    {
        $this->seedCatalog();

        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\VehicleSalesPreviewImport::class)
            ->set('csvFile', \Illuminate\Http\UploadedFile::fake()->createWithContent('l.csv', "BRAND,TYPE MODEL,CC,TRANS,FUEL,JAN,FEB,MAR,APR,MAY,JUN,JUL,AUG,SEP,OCT,NOV,DEC,TOTAL\nWULING-DBG,Air EV Baru,,,BEV,3,-,,,,,,,,,,,,3\n"))
            ->call('analyze')
            ->set('mapRawBrand', 'WULING-DBG')
            ->set('mapRawModel', 'Air EV Baru')
            ->set('mapBrandName', 'Wuling')
            ->set('mapModelName', 'Air EV')
            ->call('saveMapping')
            ->assertSee('Mapping tersimpan')
            ->assertSee('Semua kombinasi BEV sudah ter-match');

        $this->assertSame(1, VehicleNameMapping::count());
    }
}
