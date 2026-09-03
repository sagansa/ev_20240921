<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regresi mode "Semua Tahun" (year=all) endpoint publik /vehicle-market/*.
 * Kontrak: top & catalog menerima year=all → agregasi SQL lintas tahun dgn
 * respons year = null; tanpa year → tahun terbaru berdata (perilaku lama).
 */
class VehicleMarketYearAllTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Revisi 2, poin 4: endpoint vehicle-market auth required.
        Sanctum::actingAs(\App\Models\User::factory()->create(), abilities: ['*']);

        $toyota = BrandVehicle::create(['name' => 'Toyota']);
        $byd = BrandVehicle::create(['name' => 'BYD']);
        $bz4x = ModelVehicle::create(['name' => 'bZ4X', 'brand_vehicle_id' => $toyota->id]);
        $seal = ModelVehicle::create(['name' => 'Seal', 'brand_vehicle_id' => $byd->id]);

        $import2025 = SalesImport::create([
            'file_name' => 'gaikindo-2025.csv', 'source' => 'gaikindo',
            'year' => 2025, 'status' => 'processed', 'meta' => [],
        ]);
        $import2026 = SalesImport::create([
            'file_name' => 'gaikindo-2026.csv', 'source' => 'gaikindo',
            'year' => 2026, 'status' => 'processed', 'meta' => [],
        ]);

        // raw_brand = nama kanonik katalog (produksi: import mengkanonikalisasi
        // brand) agar prev_units katalog ter-cocokkan lintas nama.
        // 2025: Toyota unggul (100 vs 10) → urutan katalog per-tahun (prev_units) Toyota dulu.
        $row2025 = fn (array $o) => array_merge([
            'sales_import_id' => $import2025->id, 'year' => 2025, 'month' => null,
        ], $o);
        VehicleSalesStat::create($row2025([
            'raw_brand' => 'Toyota', 'raw_model' => 'bZ4X LR',
            'model_vehicle_id' => $bz4x->id, 'powertrain' => 'BEV', 'units' => 100,
        ]));
        VehicleSalesStat::create($row2025([
            'raw_brand' => 'BYD', 'raw_model' => 'Seal',
            'model_vehicle_id' => $seal->id, 'powertrain' => 'BEV', 'units' => 10,
        ]));

        // 2026: BYD unggul (300 vs 150) → year=all harus BYD dulu (Σ 310 > 250).
        $row2026 = fn (array $o) => array_merge([
            'sales_import_id' => $import2026->id, 'year' => 2026, 'month' => null,
        ], $o);
        VehicleSalesStat::create($row2026([
            'raw_brand' => 'Toyota', 'raw_model' => 'bZ4X LR',
            'model_vehicle_id' => $bz4x->id, 'powertrain' => 'BEV', 'units' => 150,
        ]));
        VehicleSalesStat::create($row2026([
            'raw_brand' => 'BYD', 'raw_model' => 'Seal',
            'model_vehicle_id' => $seal->id, 'powertrain' => 'BEV', 'units' => 300,
        ]));
        // Belum ter-link katalog, hanya ada di 2026 — tetap ikut agregat lintas tahun.
        VehicleSalesStat::create($row2026([
            'raw_brand' => 'XEV', 'raw_model' => 'X1', 'powertrain' => 'BEV', 'units' => 30,
        ]));
        // PHEV — masuk katalog (BEV+PHEV) tapi TIDAK masuk top powertrain=BEV.
        VehicleSalesStat::create($row2026([
            'raw_brand' => 'PHEVX', 'raw_model' => 'P5', 'powertrain' => 'PHEV', 'units' => 5,
        ]));
        // Baris bulanan — TIDAK boleh masuk agregat (top & katalog baris tahunan).
        VehicleSalesStat::create($row2026([
            'raw_brand' => 'BYD', 'raw_model' => 'Seal',
            'model_vehicle_id' => $seal->id, 'powertrain' => 'BEV', 'month' => 1, 'units' => 500,
        ]));
    }

    public function test_top_year_all_menjumlah_semua_tahun_dan_year_null(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?year=all&powertrain=BEV');

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data');
        $this->assertNull($data['year']);

        $this->assertSame([
            ['brand' => 'BYD', 'units' => 310, 'models' => 1, 'logo_url' => null],
            ['brand' => 'Toyota', 'units' => 250, 'models' => 1, 'logo_url' => null],
            ['brand' => 'XEV', 'units' => 30, 'models' => 0, 'logo_url' => null],
        ], $data['brands']);

        $this->assertSame('Seal', $data['models'][0]['model']);
        $this->assertSame(310, $data['models'][0]['units']);
        $this->assertSame('bZ4X', $data['models'][1]['model']);
        $this->assertSame(250, $data['models'][1]['units']);
        $this->assertSame('XEV', $data['models'][2]['brand']);
        $this->assertSame(30, $data['models'][2]['units']);
        $this->assertNull($data['models'][2]['model_vehicle_id']);
    }

    public function test_top_tanpa_year_tetap_tahun_terbaru(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?powertrain=BEV');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertSame(2026, $data['year']);
        $this->assertSame('BYD', $data['brands'][0]['brand']);
        $this->assertSame(300, $data['brands'][0]['units']); // 2026 saja, bukan Σ 310
        $this->assertSame('Toyota', $data['brands'][1]['brand']);
        $this->assertSame(150, $data['brands'][1]['units']);
    }

    public function test_catalog_year_all_menjumlah_dan_mengurutkan_unit(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/catalog?year=all');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertNull($data['year']);

        // Urut Σ units desc (prev_units diabaikan saat all): BYD 310 > Toyota 250 > XEV 30.
        $this->assertSame('BYD', $data['brands'][0]['brand']);
        $this->assertSame(310, $data['brands'][0]['units']);
        $this->assertSame('Toyota', $data['brands'][1]['brand']);
        $this->assertSame(250, $data['brands'][1]['units']);
        $this->assertSame('XEV', $data['brands'][2]['brand']);
        $this->assertSame(30, $data['brands'][2]['units']);

        // Model keluarga teragregasi jadi satu entri lintas tahun.
        $this->assertSame('Seal', $data['brands'][0]['models'][0]['model']);
        $this->assertSame(310, $data['brands'][0]['models'][0]['units']);
    }

    public function test_catalog_tanpa_year_tetap_tahun_terbaru_dgn_prev_units(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/catalog');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertSame(2026, $data['year']);
        // Urutan per-tahun: prev_units (2025) Toyota 100 > BYD 10 → Toyota dulu
        // meski penjualan 2026 BYD lebih besar.
        $this->assertSame('Toyota', $data['brands'][0]['brand']);
        $this->assertSame(150, $data['brands'][0]['units']);
        $this->assertSame('BYD', $data['brands'][1]['brand']);
        $this->assertSame(300, $data['brands'][1]['units']);
    }

    public function test_year_tidak_dikenal_fallback_tahun_terbaru(): void
    {
        $this->getJson('/api/v1/vehicle-market/top?year=abcd&powertrain=BEV')
            ->assertOk()->assertJsonPath('data.year', 2026);
        $this->getJson('/api/v1/vehicle-market/top?year=1899&powertrain=BEV')
            ->assertOk()->assertJsonPath('data.year', 2026);
        $this->getJson('/api/v1/vehicle-market/catalog?year=abcd')
            ->assertOk()->assertJsonPath('data.year', 2026);
    }
}
