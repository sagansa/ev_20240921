<?php

namespace Tests\Feature;

use App\Models\BrandGroup;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Fitur induk perusahaan (grup brand): leaderboard grup di /top + badge
 * grup di /catalog. Kontrak: brand tetap entitas terpisah; penggabungan
 * hanya pada dimensi groups. Brand tanpa grup = grup mandiri.
 */
class VehicleMarketGrupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // Revisi 2, poin 4: endpoint vehicle-market auth required.
        Sanctum::actingAs(\App\Models\User::factory()->create(), abilities: ['*']);

        $saic = BrandGroup::create(['name' => 'SAIC']);
        $mg = BrandVehicle::create(['name' => 'MG', 'brand_group_id' => $saic->id]);
        $wuling = BrandVehicle::create(['name' => 'Wuling', 'brand_group_id' => $saic->id]);
        $tesla = BrandVehicle::create(['name' => 'Tesla']); // tanpa grup — mandiri

        $mgModel = ModelVehicle::create(['name' => 'MG4', 'brand_vehicle_id' => $mg->id]);
        $wulingModel = ModelVehicle::create(['name' => 'Air EV', 'brand_vehicle_id' => $wuling->id]);
        $teslaModel = ModelVehicle::create(['name' => 'Model Y', 'brand_vehicle_id' => $tesla->id]);

        $import2025 = SalesImport::create([
            'file_name' => 'gaikindo-2025.csv', 'source' => 'gaikindo',
            'year' => 2025, 'status' => 'processed', 'meta' => [],
        ]);
        $import2026 = SalesImport::create([
            'file_name' => 'gaikindo-2026.csv', 'source' => 'gaikindo',
            'year' => 2026, 'status' => 'processed', 'meta' => [],
        ]);

        // 2025: MG 50 + Wuling 30 (SAIC = 80), Tesla 20.
        foreach ([[$mgModel, 'MG', 50], [$wulingModel, 'Wuling', 30]] as [$model, $raw, $units]) {
            VehicleSalesStat::create([
                'sales_import_id' => $import2025->id, 'year' => 2025, 'month' => null,
                'raw_brand' => $raw, 'raw_model' => $model->name,
                'model_vehicle_id' => $model->id, 'powertrain' => 'BEV', 'units' => $units,
            ]);
        }
        VehicleSalesStat::create([
            'sales_import_id' => $import2025->id, 'year' => 2025, 'month' => null,
            'raw_brand' => 'Tesla', 'raw_model' => 'Model Y',
            'model_vehicle_id' => $teslaModel->id, 'powertrain' => 'BEV', 'units' => 20,
        ]);

        // 2026: MG 70 + Wuling 50 (SAIC = 120), Tesla 10 — SAIC tetap teratas.
        foreach ([[$mgModel, 'MG', 70], [$wulingModel, 'Wuling', 50]] as [$model, $raw, $units]) {
            VehicleSalesStat::create([
                'sales_import_id' => $import2026->id, 'year' => 2026, 'month' => null,
                'raw_brand' => $raw, 'raw_model' => $model->name,
                'model_vehicle_id' => $model->id, 'powertrain' => 'BEV', 'units' => $units,
            ]);
        }
        VehicleSalesStat::create([
            'sales_import_id' => $import2026->id, 'year' => 2026, 'month' => null,
            'raw_brand' => 'Tesla', 'raw_model' => 'Model Y',
            'model_vehicle_id' => $teslaModel->id, 'powertrain' => 'BEV', 'units' => 10,
        ]);
    }

    public function test_top_year_all_mengagregasi_grup_lintas_tahun(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?year=all&powertrain=BEV');

        $res->assertOk();
        $data = $res->json('data');

        // SAIC = Σ(MG 120 + Wuling 80) lintas tahun, member urut desc.
        $this->assertSame('SAIC', $data['groups'][0]['group']);
        $this->assertSame(200, $data['groups'][0]['units']);
        $this->assertSame(2, $data['groups'][0]['models']);
        $this->assertSame([
            ['brand' => 'MG', 'units' => 120, 'logo_url' => null],
            ['brand' => 'Wuling', 'units' => 80, 'logo_url' => null],
        ], $data['groups'][0]['brands']);

        // Brand tanpa grup = grup mandiri atas nama raw-nya.
        $this->assertSame('Tesla', $data['groups'][1]['group']);
        $this->assertSame(30, $data['groups'][1]['units']);
        $this->assertSame([['brand' => 'Tesla', 'units' => 30, 'logo_url' => null]], $data['groups'][1]['brands']);
    }

    public function test_top_per_tahun_groups_hanya_tahun_tersebut(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/top?year=2026&powertrain=BEV');

        $res->assertOk();
        $groups = collect($res->json('data.groups'));
        $this->assertSame(120, $groups->firstWhere('group', 'SAIC')['units']);
        $this->assertSame(10, $groups->firstWhere('group', 'Tesla')['units']);
    }

    public function test_catalog_menyertakan_nama_grup_dan_null_untuk_tanpa_grup(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/catalog?year=2026');

        $res->assertOk();
        $brands = collect($res->json('data.brands'))->keyBy('brand');
        $this->assertSame('SAIC', $brands['Wuling']['group']);
        $this->assertSame('SAIC', $brands['MG']['group']);
        $this->assertNull($brands['Tesla']['group']);
    }

    public function test_seeder_idempoten_dan_case_insensitive(): void
    {
        // Jalankan dua kali — link tidak dobel dan grup tidak duplikat.
        $this->seed(\Database\Seeders\BrandGroupSeeder::class);
        $this->seed(\Database\Seeders\BrandGroupSeeder::class);

        $this->assertSame(19, BrandGroup::count());
        $saic = BrandGroup::where('name', 'SAIC')->first();
        $memberNames = $saic->brandVehicles()->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['MG', 'Wuling'], $memberNames);
    }
}
