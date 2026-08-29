<?php

namespace Tests\Feature\Api;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleMarketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $byd = BrandVehicle::create(['name' => 'BYD']);
        $wuling = BrandVehicle::create(['name' => 'Wuling']);
        $atto = ModelVehicle::create(['brand_vehicle_id' => $byd->id, 'name' => 'Atto 1', 'powertrain' => 'BEV']);
        $binguo = ModelVehicle::create(['brand_vehicle_id' => $wuling->id, 'name' => 'Binguo', 'powertrain' => 'BEV']);

        // 2025: tahun penuh (12 bulan berisi).
        $import2025 = SalesImport::create([
            'file_name' => '2025.xlsx', 'source' => 'gaikindo', 'year' => 2025,
            'period_start' => '2025-01-01', 'period_end' => '2025-12-31', 'status' => 'processed',
            'meta' => ['official' => ['grand' => ['label' => 'DOMESTIC SALES TOTAL', 'total' => 800000, 'months' => []]]],
        ]);
        foreach (range(1, 12) as $m) {
            VehicleSalesStat::create([
                'sales_import_id' => $import2025->id, 'raw_brand' => 'BYD', 'raw_model' => 'Atto 1',
                'brand_vehicle_id' => $byd->id, 'model_vehicle_id' => $atto->id, 'segment' => 'Sedan',
                'powertrain' => 'BEV', 'year' => 2025, 'month' => $m, 'units' => 1000,
            ]);
        }
        VehicleSalesStat::create([
            'sales_import_id' => $import2025->id, 'raw_brand' => 'LAIN', 'raw_model' => 'Lain-lain',
            'segment' => null, 'powertrain' => 'ICE', 'year' => 2025, 'month' => 1, 'units' => 50000,
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import2025->id, 'raw_brand' => 'BYD', 'raw_model' => 'Atto 1',
            'brand_vehicle_id' => $byd->id, 'model_vehicle_id' => $atto->id, 'segment' => 'Sedan',
            'powertrain' => 'BEV', 'year' => 2025, 'month' => null, 'units' => 12000,
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import2025->id, 'raw_brand' => 'LAIN', 'raw_model' => 'Lain-lain',
            'segment' => null, 'powertrain' => 'ICE', 'year' => 2025, 'month' => null, 'units' => 50000,
        ]);

        // 2026: partial (3 bulan), official meta grand total + bulanan.
        $import2026 = SalesImport::create([
            'file_name' => '2026.xlsx', 'source' => 'gaikindo', 'year' => 2026,
            'period_start' => '2026-01-01', 'period_end' => '2026-03-31', 'status' => 'processed',
            'meta' => ['official' => ['grand' => [
                'label' => 'DOMESTIC SALES TOTAL', 'total' => 90000,
                'months' => [1 => 30000, 2 => 30000, 3 => 30000],
            ]]],
        ]);
        foreach (range(1, 3) as $m) {
            VehicleSalesStat::create([
                'sales_import_id' => $import2026->id, 'raw_brand' => 'WULING', 'raw_model' => 'Binguo',
                'brand_vehicle_id' => $wuling->id, 'model_vehicle_id' => $binguo->id, 'segment' => 'LCGC',
                'powertrain' => 'BEV', 'year' => 2026, 'month' => $m, 'units' => 2000,
            ]);
        }
        VehicleSalesStat::create([
            'sales_import_id' => $import2026->id, 'raw_brand' => 'WULING', 'raw_model' => 'Binguo',
            'brand_vehicle_id' => $wuling->id, 'model_vehicle_id' => $binguo->id, 'segment' => 'LCGC',
            'powertrain' => 'BEV', 'year' => 2026, 'month' => null, 'units' => 6000,
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $import2026->id, 'raw_brand' => 'TOYOTA', 'raw_model' => 'Avanza',
            'segment' => 'MPV', 'powertrain' => 'ICE', 'year' => 2026, 'month' => null, 'units' => 20000,
        ]);
    }

    public function test_summary_publik_tanpa_login(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/summary');
        $res->assertOk()->assertJsonPath('success', true);

        $years = collect($res->json('data.years'))->keyBy('year');
        $this->assertEquals(12000, $years[2025]['bev_units']);
        // bev_share memakai total resmi (12000/800000).
        $this->assertEquals(0.015, $years[2025]['bev_share']);
        $this->assertTrue($years[2025]['is_full_year']);
        $this->assertFalse($years[2026]['is_full_year']);

        // Tahun terbaru 2026 partial → YoY growth tidak dihitung.
        $this->assertNull($res->json('data.latest.bev_yoy_growth'));
        $this->assertEquals(2026, $res->json('data.latest.year'));
        $this->assertEquals(6000, $res->json('data.latest.bev_units'));
    }

    public function test_trend_bulanan_dengan_market_total_resmi(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/trend?year=2026');
        $res->assertOk();

        $months = collect($res->json('data.months'));
        $this->assertCount(3, $months);
        $this->assertEquals(2000, $months->firstWhere('month', 1)['bev_units']);
        $this->assertEquals(30000, $months->firstWhere('month', 1)['market_total']);
    }

    public function test_top_default_bev(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?year=2026');
        $res->assertOk();

        $this->assertEquals('BEV', $res->json('data.powertrain'));
        $models = collect($res->json('data.models'));
        $this->assertSame('Binguo', $models->first()['model']);
        $this->assertEquals(6000, $models->first()['units']);
    }

    public function test_reimport_tahun_sama_menggantikan_angka_import_lama(): void
    {
        // Import kedua (lebih baru) utk tahun 2025: angka BEV beda.
        $reimport = SalesImport::create([
            'file_name' => '2025-clean.xlsx', 'source' => 'gaikindo', 'year' => 2025,
            'period_start' => '2025-01-01', 'period_end' => '2025-12-31', 'status' => 'processed',
            'meta' => ['official' => ['grand' => ['label' => 'DOMESTIC SALES TOTAL', 'total' => 900000, 'months' => []]]],
        ]);
        VehicleSalesStat::create([
            'sales_import_id' => $reimport->id, 'raw_brand' => 'BYD', 'raw_model' => 'Atto 1',
            'brand_vehicle_id' => null, 'model_vehicle_id' => null, 'segment' => 'Sedan',
            'powertrain' => 'BEV', 'year' => 2025, 'month' => null, 'units' => 47100,
        ]);

        // Hanya angka dari import terbaru yang dihitung — 12.000 (import lama)
        // tidak ikut disum.
        $years = collect($this->getJson('/api/v1/vehicle-market/summary')->json('data.years'))->keyBy('year');
        $this->assertEquals(47100, $years[2025]['bev_units']);

        // Trend tahun lama juga hanya dari import terbaru (bulan tidak ada lagi).
        $trendMonths = collect($this->getJson('/api/v1/vehicle-market/trend?year=2025')->json('data.months'));
        $this->assertCount(0, $trendMonths);
    }

    public function test_trend_tanpa_year_pakai_tahun_data_bulanan_terbaru(): void
    {
        // Hapus import 2026 → tahun bulanan terbaru tinggal 2025. Default
        // TIDAK boleh now()->year (tahun berjalan kosong = chart rusak).
        SalesImport::where('year', 2026)->delete();

        $res = $this->getJson('/api/v1/vehicle-market/trend');
        $res->assertOk();
        $this->assertEquals(2025, $res->json('data.year'));
        $this->assertCount(12, $res->json('data.months'));
    }

    public function test_trend_filter_brand_dan_model(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/trend?year=2026&brand=WULING&model=Binguo');
        $res->assertOk();

        $months = collect($res->json('data.months'));
        $this->assertCount(3, $months);
        $this->assertEquals(2000, $months->firstWhere('month', 1)['bev_units']);
        // market_total saat terfilter = hasil parse (2000), BUKAN total resmi
        // nasional (30000) — angka resmi hanya berlaku utk scope nasional.
        $this->assertEquals(2000, $months->firstWhere('month', 1)['market_total']);
    }

    public function test_trend_default_year_ikut_brand_filter(): void
    {
        // BYD hanya punya baris bulanan di 2025 → default = 2025.
        $res = $this->getJson('/api/v1/vehicle-market/trend?brand=BYD');
        $res->assertOk();
        $this->assertEquals(2025, $res->json('data.year'));
        $this->assertCount(12, $res->json('data.months'));
    }

    public function test_top_tanpa_year_pakai_tahun_punya_data_model(): void
    {
        // 2026 punya baris level model → default 2026 (perilaku lama max(year)).
        $res = $this->getJson('/api/v1/vehicle-market/top');
        $res->assertOk();
        $this->assertEquals(2026, $res->json('data.year'));
        $this->assertNotSame([], $res->json('data.models'));
    }

    public function test_top_tanpa_year_fallback_bila_tahun_baru_tanpa_model(): void
    {
        // Skenario produksi: 2026 baru punya rekap, belum ada baris level
        // model → default harus 2025 (yang punya model), BUKAN 2026 kosong.
        VehicleSalesStat::where('year', 2026)->whereNotNull('model_vehicle_id')->delete();

        $res = $this->getJson('/api/v1/vehicle-market/top');
        $res->assertOk();
        $this->assertEquals(2025, $res->json('data.year'));
        $this->assertNotSame([], $res->json('data.models'));
    }

    public function test_top_filter_brand(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?year=2025&brand=BYD');
        $res->assertOk();

        $models = collect($res->json('data.models'));
        $this->assertCount(1, $models);
        $this->assertSame('Atto 1', $models->first()['model']);

        $brands = collect($res->json('data.brands'));
        $this->assertCount(1, $brands);
        $this->assertSame('BYD', $brands->first()['brand']);
    }
}
