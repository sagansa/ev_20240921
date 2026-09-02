<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Endpoint komposisi kategori kendaraan (/vehicle-market/composition):
 * share = units/total, uncategorized terpisah, year=all agregat,
 * filter powertrain bekerja.
 */
class VehicleCompositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $brand = BrandVehicle::create(['name' => 'Daihatsu']);
        $suv = ModelVehicle::create(['name' => 'Terano', 'brand_vehicle_id' => $brand->id, 'category' => 'SUV']);
        $mpv = ModelVehicle::create(['name' => 'Xenia', 'brand_vehicle_id' => $brand->id, 'category' => 'MPV']);
        $pickup = ModelVehicle::create(['name' => 'Gran Max Pu', 'brand_vehicle_id' => $brand->id, 'category' => 'Pickup']);
        $mist = ModelVehicle::create(['name' => 'Mistery', 'brand_vehicle_id' => $brand->id, 'category' => null]);

        $import2025 = SalesImport::create([
            'file_name' => 'gaikindo-2025.csv', 'source' => 'gaikindo',
            'year' => 2025, 'status' => 'processed', 'meta' => [],
        ]);
        $import2026 = SalesImport::create([
            'file_name' => 'gaikindo-2026.csv', 'source' => 'gaikindo',
            'year' => 2026, 'status' => 'processed', 'meta' => [],
        ]);

        $annual = fn (array $over) => array_merge([
            'sales_import_id' => $import2026->id, 'year' => 2026, 'month' => null,
        ], $over);

        // 2026: SUV 400 + MPV 100 + Pickup 50 + tanpa-kategori 30 + unlinked 70 = 650.
        VehicleSalesStat::create($annual([
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Terano',
            'model_vehicle_id' => $suv->id, 'powertrain' => 'BEV', 'units' => 400,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Xenia',
            'model_vehicle_id' => $mpv->id, 'powertrain' => 'BEV', 'units' => 100,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Gran Max Pu',
            'model_vehicle_id' => $pickup->id, 'powertrain' => 'BEV', 'units' => 50,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Mistery',
            'model_vehicle_id' => $mist->id, 'powertrain' => 'BEV', 'units' => 30,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'RAWX', 'raw_model' => 'Tidak Dikatalog', 'powertrain' => 'BEV', 'units' => 70,
        ]));
        // HEV — tidak masuk powertrain=BEV, masuk ALL.
        VehicleSalesStat::create($annual([
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Terano HEV',
            'model_vehicle_id' => $suv->id, 'powertrain' => 'HEV', 'units' => 50,
        ]));

        // 2025 (untuk year=all): SUV 100.
        VehicleSalesStat::create([
            'sales_import_id' => $import2025->id, 'year' => 2025, 'month' => null,
            'raw_brand' => 'Daihatsu', 'raw_model' => 'Terano',
            'model_vehicle_id' => $suv->id, 'powertrain' => 'BEV', 'units' => 100,
        ]);
    }

    public function test_komposisi_per_tahun_share_dan_uncategorized(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/composition?year=2026&powertrain=BEV');

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data');

        $this->assertSame(2026, $data['year']);
        $this->assertSame('BEV', $data['powertrain']);
        $this->assertSame(650, $data['total_units']);
        $this->assertSame(100, $data['uncategorized_units']);

        $cats = collect($data['categories'])->keyBy('category');
        $this->assertSame(400, $cats['SUV']['units']);
        $this->assertSame(0.6154, $cats['SUV']['share']);
        $this->assertSame('Penumpang', $cats['SUV']['group']);
        $this->assertSame(100, $cats['MPV']['units']);
        $this->assertSame(0.1538, $cats['MPV']['share']);
        $this->assertSame('Komersial', $cats['Pickup']['group']);
        // Urut units desc: SUV > MPV > Pickup.
        $this->assertSame(['SUV', 'MPV', 'Pickup'], array_column($data['categories'], 'category'));
    }

    public function test_komposisi_year_all_menjumlah_lintas_tahun(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/composition?year=all&powertrain=BEV');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertNull($data['year']);
        $this->assertSame(750, $data['total_units']); // 650 + 100

        $cats = collect($data['categories'])->keyBy('category');
        $this->assertSame(500, $cats['SUV']['units']);
        $this->assertSame(0.6667, $cats['SUV']['share']);
    }

    public function test_komposisi_powertrain_all_menyertakan_hev(): void
    {
        $res = $this->getJson('/api/v1/vehicle-market/composition?year=2026&powertrain=ALL');

        $res->assertOk();
        $data = $res->json('data');
        $this->assertSame(700, $data['total_units']); // 650 + HEV 50

        $cats = collect($data['categories'])->keyBy('category');
        $this->assertSame(450, $cats['SUV']['units']); // 400 + 50
    }

    public function test_powertrain_tak_dikenal_ditolak_validasi(): void
    {
        // Konsisten dgn /top: powertrain di luar enum → 422 (bukan fallback diam-diam).
        $this->getJson('/api/v1/vehicle-market/composition?year=2026&powertrain=XYZ')
            ->assertStatus(422);
    }
}
