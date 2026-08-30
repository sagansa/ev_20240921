<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\VehicleNameMapping;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Preview dry-run + gerbang --require-full-link: pastikan data baru pada
 * laporan penjualan TERLIHAT dulu sebelum masuk stats (base-first).
 */
class PreviewVehicleSalesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function writeCsv(array $rows): string
    {
        $dir = storage_path('framework/testing/preview-vehicle-sales');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid('gaikindo-', true).'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, ['BRAND', 'TYPE MODEL', 'CC', 'TRANS', 'FUEL', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC', 'TOTAL']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->tempFiles[] = $path;

        return $path;
    }

    private function seedCatalog(): void
    {
        $byd = BrandVehicle::create(['name' => 'BYD']);
        ModelVehicle::create([
            'name' => 'Atto 1', 'brand_vehicle_id' => $byd->id, 'powertrain' => 'BEV',
        ]);
    }

    public function test_preview_melaporkan_kombinasi_baru_tanpa_menulis_db(): void
    {
        $this->seedCatalog();

        $brandsBefore = BrandVehicle::count();
        $modelsBefore = ModelVehicle::count();
        $importsBefore = SalesImport::count();
        $statsBefore = VehicleSalesStat::count();

        $csv = $this->writeCsv([
            // Ter-match (Atto 1 → varian "Atto 1 Dynamic" skor 70).
            ['BYD', 'Atto 1 Dynamic', '100', 'AT', 'BEV', '5', '-', '', '', '', '', '', '', '', '', '', '', '5'],
            // Model baru di brand existing.
            ['BYD', 'Sealion 8', '', '', 'BEV', '7', '-', '', '', '', '', '', '', '', '', '', '', '7'],
            // Brand baru.
            ['WULING-DBG', 'Air EV Baru', '', '', 'BEV', '3', '-', '', '', '', '', '', '', '', '', '', '', '3'],
            // Non-BEV — by design tidak ter-link, bukan "baru".
            ['TOYOTA', 'Agya 1.2 G', '1200', 'AT', 'G', '9', '-', '', '', '', '', '', '', '', '', '', '', '9'],
        ]);

        $this->artisan('vehicle-sales:preview', ['file' => $csv, '--year' => '2026'])
            ->expectsOutputToContain('Kombinasi BARU (perlu keputusan)')
            ->assertSuccessful();

        // DRY-RUN: tidak ada yang ditulis.
        $this->assertSame($brandsBefore, BrandVehicle::count());
        $this->assertSame($modelsBefore, ModelVehicle::count());
        $this->assertSame($importsBefore, SalesImport::count());
        $this->assertSame($statsBefore, VehicleSalesStat::count());
    }

    public function test_preview_export_new_menghasilkan_csv_siap_connecting(): void
    {
        $this->seedCatalog();

        $out = storage_path('framework/testing/preview-vehicle-sales/export-'.uniqid().'.csv');
        $this->tempFiles[] = $out;

        $csv = $this->writeCsv([
            ['BYD', 'Sealion 8', '', '', 'BEV', '7', '-', '', '', '', '', '', '', '', '', '', '', '7'],
        ]);

        $this->artisan('vehicle-sales:preview', [
            'file' => $csv, '--year' => '2026', '--export-new' => $out,
        ])->assertSuccessful();

        $content = file_get_contents($out);
        $this->assertStringContainsString('BRAND,MODEL,TYPE,POWERTRAIN,CATEGORY,SIZE', $content);
        $this->assertStringContainsString('Sealion 8', $content);
        $this->assertStringContainsString('BEV', $content);
    }

    public function test_require_full_link_menolak_import_bila_ada_bev_baru(): void
    {
        $this->seedCatalog();

        $csv = $this->writeCsv([
            ['BYD', 'Atto 1', '100', 'AT', 'BEV', '5', '-', '', '', '', '', '', '', '', '', '', '', '5'],
            ['BYD', 'Sealion 8', '', '', 'BEV', '7', '-', '', '', '', '', '', '', '', '', '', '', '7'],
        ]);

        $this->artisan('vehicle-sales:import-csv', [
            'file' => $csv, '--year' => '2026', '--require-full-link' => true,
        ])->assertFailed();

        // Tidak ada yang ditulis.
        $this->assertSame(0, SalesImport::count());
        $this->assertSame(0, VehicleSalesStat::count());
    }

    public function test_require_full_link_lulus_bila_semua_bev_termatch(): void
    {
        $this->seedCatalog();

        $csv = $this->writeCsv([
            ['BYD', 'Atto 1 Dynamic', '100', 'AT', 'BEV', '5', '-', '', '', '', '', '', '', '', '', '', '', '5'],
            ['TOYOTA', 'Agya 1.2 G', '1200', 'AT', 'G', '9', '-', '', '', '', '', '', '', '', '', '', '', '9'],
        ]);

        $this->artisan('vehicle-sales:import-csv', [
            'file' => $csv, '--year' => '2026', '--require-full-link' => true,
        ])->assertSuccessful();

        // BEV ter-link, non-BEV masuk stats dengan link NULL (by design).
        $this->assertSame(1, ModelVehicle::count());
        $this->assertSame(2, VehicleSalesStat::whereNotNull('model_vehicle_id')->count());
    }


    public function test_mapping_eksplisit_menang_dan_preview_menandai_termatch(): void
    {
        $this->seedCatalog();

        // Raw menyimpang total — hanya bisa ter-match lewat mapping tabel.
        VehicleNameMapping::record('WULING-DBG', 'Air EV Baru', 'BYD', 'Atto 1', null, 'data hantu digabung');

        $preview = app(\App\Services\VehicleSalesMatcher::class)
            ->preview('WULING-DBG', 'Air EV Baru', 'Air EV Baru Max');

        $this->assertFalse($preview['brand_new']);
        $this->assertFalse($preview['model_new']);
        $this->assertTrue($preview['mapping_used']);
        $this->assertSame('BYD', $preview['brand_name']);
        $this->assertSame('Atto 1', $preview['model_name']);
    }

    public function test_match_dengan_mapping_menghubungkan_katalog_tanpa_auto_create(): void
    {
        $this->seedCatalog();
        VehicleNameMapping::record('WULING-DBG', 'Air EV Baru', 'BYD', 'Atto 1');

        $brandsBefore = BrandVehicle::count();
        $modelsBefore = ModelVehicle::count();

        $match = app(\App\Services\VehicleSalesMatcher::class)
            ->match('WULING-DBG', 'Air EV Baru', null, 'Air EV Baru Max');

        $this->assertTrue($match['mapping_used']);
        $this->assertSame('BYD', BrandVehicle::find($match['brand_vehicle_id'])->name);
        $this->assertSame('Atto 1', ModelVehicle::find($match['model_vehicle_id'])->name);
        // Tidak ada auto-create baru.
        $this->assertSame($brandsBefore, BrandVehicle::count());
        $this->assertSame($modelsBefore, ModelVehicle::count());
    }
}
