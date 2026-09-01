<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyVehicleConnectingTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) @unlink($path);
        parent::tearDown();
    }

    private function writeCsv(array $rows): string
    {
        $path = storage_path('app/verify-'.uniqid().'.csv');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['BRAND MODEL TYPE', 'FUEL', 'BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);
        foreach ($rows as $r) fputcsv($handle, $r);
        fclose($handle);
        $this->tempFiles[] = $path;
        return $path;
    }

    private function seedCatalog(): void
    {
        $gac = BrandVehicle::create(['name' => 'GAC']);
        $aion = ModelVehicle::create([
            'name' => 'AION', 'brand_vehicle_id' => $gac->id,
            'powertrain' => 'BEV', 'category' => 'Sedan', 'size_class' => 'Medium',
        ]);
        TypeVehicle::create(['name' => 'ES', 'model_vehicle_id' => $aion->id, 'type_charger' => []]);
    }

    public function test_melaporkan_match_klasifikasi_beda_dan_entitas_baru(): void
    {
        $this->seedCatalog();

        $csv = $this->writeCsv([
            // Match penuh (pencocokan case-insensitive).
            ['AION AION ES', 'EV', 'gac', 'aion', 'es', 'BEV', 'Sedan', 'Medium'],
            // Klasifikasi beda: category CSV=SUV vs DB=Sedan.
            ['AION AION ES', 'EV', 'GAC', 'AION', 'ES', 'BEV', 'SUV', 'Medium'],
            // Model baru di brand existing.
            ['GAC AION V', 'EV', 'GAC', 'AION V', '', 'BEV', 'SUV', 'Small'],
            // Brand baru.
            ['WULING Air EV', 'EV', 'WULING', 'Air EV', '', 'BEV', 'City Car', ''],
        ]);

        $this->artisan('vehicle-connecting:verify', [
            'csv' => $csv, '--json' => storage_path('app/verify-out.json'),
        ])
            ->expectsOutputToContain('✓ Sama: 1')
            ->expectsOutputToContain('MODEL BARU (brand ada, model belum): 1')
            ->expectsOutputToContain('BRAND BARU (akan dibuat oleh Import): 1')
            ->expectsOutputToContain('KLASIFIKASI BEDA (perlu Import/backfill): 1')
            ->assertSuccessful();

        $report = json_decode(file_get_contents(storage_path('app/verify-out.json')), true);
        $klasifikasi = array_values($report['klasifikasiBeda']);
        $this->assertCount(1, $klasifikasi);
        $this->assertSame('GAC', $klasifikasi[0]['brand']);
        $this->assertStringContainsString('CSV=SUV', $klasifikasi[0]['diff']);
        $this->assertSame('WULING', $report['brandBaru'][0]['brand']);
    }

    public function test_melaporkan_model_db_yang_tidak_ada_di_csv(): void
    {
        $this->seedCatalog();

        $csv = $this->writeCsv([
            // CSV tidak mereferensikan model AION → dilaporkan sebagai tinjauan.
            ['WULING Air EV', 'EV', 'WULING', 'Air EV', '', 'BEV', 'City Car', ''],
        ]);

        $this->artisan('vehicle-connecting:verify', ['csv' => $csv])
            ->expectsOutputToContain('DI DB, TIDAK ADA DI CSV')
            ->expectsOutputToContain('MODEL DB TIDAK ADA DI CSV: 1')
            ->assertSuccessful();
    }
}
