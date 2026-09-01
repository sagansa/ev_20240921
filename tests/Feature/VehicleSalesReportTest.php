<?php

namespace Tests\Feature;

use App\Filament\Pages\VehicleSalesReport;
use App\Filament\Resources\Panel\SalesImportResource;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\User;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BrandVehicle $brand;

    protected ModelVehicle $avanza;

    protected ModelVehicle $bz4x;

    protected TypeVehicle $type;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->brand = BrandVehicle::create(['name' => 'Toyota']);
        $this->avanza = ModelVehicle::create([
            'name' => 'Avanza',
            'brand_vehicle_id' => $this->brand->id
        ]);
        $this->bz4x = ModelVehicle::create([
            'name' => 'bZ4X',
            'brand_vehicle_id' => $this->brand->id
        ]);
        $this->type = TypeVehicle::create([
            'name' => 'Avanza 1.5 G',
            'model_vehicle_id' => $this->avanza->id,
            'type_charger' => [],
        ]);

        // Dua import utk tahun yang sama: hanya yang TERBARU yang boleh dihitung.
        $oldImport = SalesImport::create([
            'file_name' => 'old-2026.csv',
            'source' => 'gaikindo',
            'year' => 2026,
            'status' => 'processed',
            'meta' => [],
        ]);
        $newImport = SalesImport::create([
            'file_name' => 'new-2026.csv',
            'source' => 'gaikindo',
            'year' => 2026,
            'status' => 'processed',
            'meta' => [],
        ]);

        $annual = fn (array $overrides) => array_merge([
            'sales_import_id' => $newImport->id,
            'year' => 2026,
            'month' => null, // baris agregat tahunan
        ], $overrides);

        // Import lama — 999 unit TIDAK boleh ikut (dedup latest import).
        VehicleSalesStat::create($annual([
            'sales_import_id' => $oldImport->id,
            'raw_brand' => 'TOYOTA',
            'raw_model' => 'Avanza 1.5 G',
            'brand_vehicle_id' => $this->brand->id,
            'model_vehicle_id' => $this->avanza->id,
            'type_vehicle_id' => $this->type->id,
            'powertrain' => 'ICE',
            'units' => 999,
        ]));

        // Import terbaru — baris tahunan yang sah.
        VehicleSalesStat::create($annual([
            'raw_brand' => 'TOYOTA',
            'raw_model' => 'Avanza 1.5 G',
            'brand_vehicle_id' => $this->brand->id,
            'model_vehicle_id' => $this->avanza->id,
            'type_vehicle_id' => $this->type->id,
            'powertrain' => 'ICE',
            'units' => 120,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'TOYOTA',
            'raw_model' => 'bZ4X Long Range',
            'brand_vehicle_id' => $this->brand->id,
            'model_vehicle_id' => $this->bz4x->id,
            'type_vehicle_id' => null,
            'powertrain' => 'BEV',
            'units' => 50,
        ]));
        VehicleSalesStat::create($annual([
            'raw_brand' => 'UNKNOWN BRAND',
            'raw_model' => 'Unknown Model',
            'brand_vehicle_id' => null,
            'model_vehicle_id' => null,
            'type_vehicle_id' => null,
            'powertrain' => 'ICE',
            'units' => 7,
        ]));

        // Baris BULANAN di import terbaru — TIDAK boleh ikut disum (dobel).
        VehicleSalesStat::create($annual([
            'month' => 1,
            'raw_brand' => 'TOYOTA',
            'raw_model' => 'Avanza 1.5 G',
            'brand_vehicle_id' => $this->brand->id,
            'model_vehicle_id' => $this->avanza->id,
            'type_vehicle_id' => $this->type->id,
            'powertrain' => 'ICE',
            'units' => 500,
        ]));
    }

    public function test_navigasi_terdaftar_pada_grup_referensi_kendaraan(): void
    {
        $this->assertSame('Referensi Kendaraan', VehicleSalesReport::getNavigationGroup());
        $this->assertSame('Laporan Penjualan', VehicleSalesReport::getNavigationLabel());
        $this->assertNotNull(VehicleSalesReport::getNavigationIcon());
        $this->assertGreaterThan(
            SalesImportResource::getNavigationSort(),
            VehicleSalesReport::getNavigationSort(),
        );
    }

    public function test_brand_rows_menghitung_import_terbaru_baris_tahunan_saja(): void
    {
        $rows = VehicleSalesReport::brandRows(2026, 'ALL');

        $this->assertSame([
            [
                'brand' => 'Toyota',
                'total_units' => 170, // 120 + 50; BUKAN 999 dari import lama / +500 bulanan
                'model_count' => 2,
                'type_count' => 1,
            ],
            [
                'brand' => '(tidak ter-match)',
                'total_units' => 7,
                'model_count' => 1, // distinct raw_model
                'type_count' => 0,
            ],
        ], $rows);

        $this->assertSame(177, VehicleSalesReport::totalUnits(2026, 'ALL'));
    }

    public function test_model_rows_urut_unit_desc_dengan_powertrain_katalog(): void
    {
        $rows = VehicleSalesReport::modelRows(2026, 'ALL');

        $this->assertSame('Avanza', $rows[0]['model']);
        $this->assertSame(120, $rows[0]['total_units']);
        $this->assertSame('Toyota', $rows[0]['brand']);
        $this->assertSame(1, $rows[0]['type_count']);

        $this->assertSame('bZ4X', $rows[1]['model']);
        $this->assertSame(50, $rows[1]['total_units']);
        $this->assertSame(0, $rows[1]['type_count']);

        $last = end($rows);
        $this->assertSame('(tidak ter-match)', $last['model']);
        $this->assertSame(7, $last['total_units']);
    }

    public function test_filter_powertrain(): void
    {
        $bev = VehicleSalesReport::brandRows(2026, 'BEV');

        $this->assertSame([
            ['brand' => 'Toyota', 'total_units' => 50, 'model_count' => 1, 'type_count' => 0],
        ], $bev);

        $this->assertSame(50, VehicleSalesReport::totalUnits(2026, 'BEV'));
        $this->assertSame(0, VehicleSalesReport::totalUnits(2026, 'HEV'));
    }

    public function test_tahun_tersedia_default_tahun_terbaru(): void
    {
        $this->assertSame(2026, VehicleSalesReport::latestYear());
        $this->assertSame([2026 => 2026], VehicleSalesReport::availableYears());
    }

    public function test_model_rows_dibatasi_seratus_baris(): void
    {
        $import = SalesImport::query()->orderByDesc('id')->firstOrFail();

        // 105 model tambahan → tabel per model harus terpotong di 100.
        for ($i = 1; $i <= 105; $i++) {
            $model = ModelVehicle::create([
                'name' => "Model Padat $i",
                'brand_vehicle_id' => $this->brand->id,
            ]);
            VehicleSalesStat::create([
                'sales_import_id' => $import->id,
                'raw_brand' => 'TOYOTA',
                'raw_model' => "Model Padat $i",
                'brand_vehicle_id' => $this->brand->id,
                'model_vehicle_id' => $model->id,
                'powertrain' => 'ICE',
                'year' => 2026,
                'month' => null,
                'units' => 1,
            ]);
        }

        $rows = VehicleSalesReport::modelRows(2026, 'ALL');

        // Batasan 100 baris berlaku utk model katalog; baris "(tidak ter-match)"
        // tetap ditambahkan di akhir sebagai rekap.
        $matchedRows = array_values(array_filter(
            $rows,
            fn (array $row) => $row['model'] !== '(tidak ter-match)',
        ));

        $this->assertCount(VehicleSalesReport::MODEL_ROW_LIMIT, $matchedRows);
        $this->assertSame('(tidak ter-match)', end($rows)['model']);
    }

    public function test_halaman_render_dengan_angka_agregat(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/vehicle-sales-report');

        $response->assertSuccessful();
        $response->assertSee('Toyota');
        $response->assertSee('170');
        $response->assertSee('(tidak ter-match)');
        $response->assertSee('Avanza');
        $response->assertSee('bZ4X');
    }
}
