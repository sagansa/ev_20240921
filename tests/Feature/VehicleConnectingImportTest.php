<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleConnectingImportTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(array $rows): string
    {
        $path = storage_path('app/connecting-'.uniqid().'.csv');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['BRAND MODEL TYPE', 'FUEL', 'BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);
        foreach ($rows as $r) fputcsv($handle, $r);
        fclose($handle);
        return $path;
    }

    public function test_import_resolves_catalog_links_dan_idempoten(): void
    {
        $gac = BrandVehicle::create(['name' => 'GAC']);
        $aion = ModelVehicle::create(['name' => 'AION', 'brand_vehicle_id' => $gac->id]);
        TypeVehicle::create(['name' => 'ES', 'model_vehicle_id' => $aion->id, 'type_charger' => []]);

        $csv = $this->writeCsv([
            // Baris dengan newline artefak Excel — harus dibersihkan.
            ["GAC\nAION ES", 'EV', 'GAC', 'AION', 'ES', 'BEV', 'Sedan', 'Medium'],
        ]);

        $this->artisan('vehicle-connecting:import', ['csv' => $csv])->assertSuccessful();

        $row = VehicleConnecting::query()->firstOrFail();
        $this->assertSame('GAC AION ES', $row->raw_gabungan); // newline dibersihkan
        $this->assertSame($gac->id, $row->brand_vehicle_id);
        $this->assertSame($aion->id, $row->model_vehicle_id);
        $this->assertSame('BEV', $row->powertrain);
        $this->assertSame('Sedan', $row->category);

        // Idempoten: run kedua tidak menambah/mengubah.
        $this->artisan('vehicle-connecting:import', ['csv' => $csv])->assertSuccessful();
        $this->assertSame(1, VehicleConnecting::count());
    }

    public function test_prune_menghapus_baris_yang_tidak_ada_di_csv(): void
    {
        VehicleConnecting::create([
            'raw_gabungan' => 'OLD ROW', 'brand_vehicle_id' => null, 'powertrain' => 'ICE',
        ]);

        $csv = $this->writeCsv([
            ['WULING Air EV', 'EV', 'WULING', 'Air EV', '', 'BEV', 'City Car', ''],
        ]);

        $this->artisan('vehicle-connecting:import', ['csv' => $csv, '--prune' => true])
            ->expectsOutputToContain('Baris pruned')
            ->assertSuccessful();

        $this->assertSame(1, VehicleConnecting::count());
        $this->assertSame('WULING Air EV', VehicleConnecting::query()->value('raw_gabungan'));
    }

    public function test_baris_tanpa_model_tetap_tersimpan_dengan_link_brand(): void
    {
        $honda = BrandVehicle::create(['name' => 'HONDA']);

        $csv = $this->writeCsv([
            ['HONDA', '', 'HONDA', '', '', 'ICE', '', ''],
        ]);

        $this->artisan('vehicle-connecting:import', ['csv' => $csv])->assertSuccessful();

        $row = VehicleConnecting::query()->firstOrFail();
        $this->assertSame($honda->id, $row->brand_vehicle_id);
        $this->assertNull($row->model_vehicle_id);
    }
}
