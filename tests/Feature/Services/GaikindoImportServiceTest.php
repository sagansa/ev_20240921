<?php

namespace Tests\Feature\Services;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\VehicleSalesStat;
use App\Services\GaikindoImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GaikindoImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = storage_path('app/test-gaikindo-'.uniqid());
        File::ensureDirectoryExists($this->fixtureDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureDir);
        parent::tearDown();
    }

    public function test_import_format_2026_tanpa_kolom_fuel(): void
    {
        $file = $this->fixtureDir.'/wholesales_janjul2026.xlsx';
        $this->writeFixture2026($file);

        $summary = app(GaikindoImportService::class)->importFromFile($file, 2026);

        // Angka resmi: label di baris kedua dari bawah, angka di baris berikutnya.
        $this->assertSame(42465, $summary['official_total']);
        $this->assertEqualsWithDelta(0.916, $summary['coverage'], 0.01);
        $this->assertSame('processed', $summary['status']);

        // Klasifikasi powertrain.
        $powertrains = VehicleSalesStat::query()->whereNull('month')->get()
            ->mapWithKeys(fn ($s) => [$s->raw_model => $s->powertrain]);
        $this->assertSame('HEV', $powertrains['Veloz 1.5 V HEV A/T']);
        $this->assertSame('BEV', $powertrains['Atto 1 Dynamic']);
        $this->assertSame('BEV', $powertrains['Air EV']);
        $this->assertSame('ICE', $powertrains['Brio Satya E']);

        // Float Eropa: 10.512 = 10.512 unit.
        $this->assertSame(10512, (int) VehicleSalesStat::query()
            ->where('raw_model', 'Veloz 1.5 V HEV A/T')->where('month', 1)->value('units'));

        // Katalog: model baru BEV dengan kWh → type default battery_capacity.
        $atto = ModelVehicle::whereHas('brandVehicle', fn ($q) => $q->where('name', 'BYD'))
            ->where('name', 'Atto 1 Dynamic')->first();
        $this->assertNotNull($atto);
        $this->assertSame('BEV', $atto->powertrain);
        $type = TypeVehicle::where('model_vehicle_id', $atto->id)->first();
        $this->assertNotNull($type);
        $this->assertEquals(51.8, (float) $type->battery_capacity);

        // BEV tanpa kWh tetap ter-import tanpa type.
        $airEv = ModelVehicle::where('name', 'Air EV')->first();
        $this->assertNotNull($airEv);
        $this->assertSame('BEV', $airEv->powertrain);
        $this->assertSame(0, TypeVehicle::where('model_vehicle_id', $airEv->id)->count());

        // Segment dari section header.
        $this->assertSame('Sedan', VehicleSalesStat::where('raw_model', 'Atto 1 Dynamic')->value('segment'));

        // Import tercatat.
        $this->assertSame(1, SalesImport::count());
        $this->assertSame(2026, SalesImport::first()->year);
    }

    public function test_import_format_fuel_column_dengan_alias_brand(): void
    {
        $file = $this->fixtureDir.'/wholesales_jandec2023.xlsx';
        $this->writeFixtureFuelColumn($file);

        $summary = app(GaikindoImportService::class)->importFromFile($file, 2023);

        $this->assertSame('BEV', VehicleSalesStat::where('raw_model', 'Ioniq5')->value('powertrain'));
        $this->assertSame('ICE', VehicleSalesStat::where('raw_model', 'Brio Satya E')->value('powertrain'));

        // Alias "HYUNDAI - HMID" harus match/creating satu brand "Hyundai".
        $hyundai = BrandVehicle::where('name', 'Hyundai')->get();
        $this->assertCount(1, $hyundai);
        $this->assertSame($hyundai->first()->id, VehicleSalesStat::where('raw_model', 'Ioniq5')->value('brand_vehicle_id'));

        $this->assertGreaterThan(0, $summary['stat_rows']);
    }

    /**
     * Layout buku cetak (file bersih GAIKINDO_YYYY.xlsx): baris rekap adalah
     * SATU sel gabungan raksasa berisi teks + deretan angka ribuan-koma + %.
     * Parser harus mengekstrak dari teks dengan konteks jumlah bulan.
     */
    public function test_import_rekap_sel_gabungan_terekstrak(): void
    {
        $file = $this->fixtureDir.'/wholesales_merged_recap.xlsx';
        $rows = [
            ['NO', 'BRAND', 'TYPE', 'CC', 'TANK', 'JAN', 'FEB', 'MAR', 'APR', 'TOTAL'],
            ['SEDAN'],
            [1, 'TOYOTA', 'Veloz 1.5 V HEV A/T', '1500', '45 L', 10.512, 20103, 5025, 1000, 36640],
            [2, 'HONDA', 'Brio Satya E', '1200', '40 L', 500, 400, 300, 100, 1300],
            [3, 'WULING', 'Air EV', '-', '', 400, 300, 200, 75, 975],
            ['TOTAL', null, null, null, null, 11512, 20753, 5475, 1175, 38915],
            [],
            // Simulasi sel gabungan raksasa (nilai menempel dalam satu string).
            ['PASSENGER CAR SALES TOTAL DOMESTIC SALES CUMULATIVE 12,512 20,753 5,475 1,175 42,465 11,512 32,265 37,740 44,485 100%'],
        ];

        $this->writeXlsx($file, 'Page 3 Table 1', $rows);

        $summary = app(GaikindoImportService::class)->importFromFile($file, 2024);

        $this->assertSame(42465, $summary['official_total']);
        $this->assertSame('processed', $summary['status']);
        $grand = $summary['official']['grand'];
        $this->assertEquals([1 => 12512, 2 => 20753, 3 => 5475, 4 => 1175], $grand['months']);
    }

    public function test_import_ditolak_bila_baris_resmi_tidak_ada(): void
    {
        $file = $this->fixtureDir.'/wholesales_no_official.xlsx';
        $rows = [
            ['NO', 'BRAND', 'TYPE', 'CC', 'TANK', 'JAN', 'FEB', 'MAR', 'APR', 'TOTAL'],
            ['SEDAN'],
            [1, 'TOYOTA', 'Veloz 1.5 V HEV A/T', '1500', '45 L', 10.512, 20103, 5025, 1000, 36640],
            [2, 'HONDA', 'Brio Satya E', '1200', '40 L', 500, 400, 300, 100, 1300],
            ['TOTAL', null, null, null, null, 11512, 20753, 5475, 1175, 38915],
        ];
        $this->writeXlsx($file, 'Table 1', $rows);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOMESTIC SALES TOTAL');

        try {
            app(GaikindoImportService::class)->importFromFile($file, 2024);
        } finally {
            // Transaksi rollback → tidak ada jejak data.
            $this->assertSame(0, SalesImport::count());
            $this->assertSame(0, VehicleSalesStat::count());
        }
    }

    public function test_import_ditolak_bila_over_parse_kontaminasi_kumulatif(): void
    {
        $file = $this->fixtureDir.'/wholesales_cumulative.xlsx';
        // Kolom TOTAL berisi kumulatif tahunan yang terbaca sebagai baris model
        // → total terparse jauh melampaui angka resmi.
        $rows = [
            ['NO', 'BRAND', 'TYPE', 'CC', 'TANK', 'JAN', 'FEB', 'MAR', 'APR', 'TOTAL'],
            ['SEDAN'],
            [1, 'TOYOTA', 'Veloz 1.5 V HEV A/T', '1500', '45 L', 10.512, 20103, 5025, 1000, 36640],
            ['SEMUA MODEL', 'XPENG VINFAST CAMPURAN', '-', '', 5000.512, 3000.103, 2000.25, 1000, 42000],
            [],
            ['DOMESTIC SALES TOTAL'],
            [null, null, null, null, null, 12512, 21103, 6425, 2225, 42465],
        ];
        $this->writeXlsx($file, 'Table 1', $rows);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bersih');

        try {
            app(GaikindoImportService::class)->importFromFile($file, 2025);
        } finally {
            $this->assertSame(0, SalesImport::count());
            $this->assertSame(0, VehicleSalesStat::count());
        }
    }

    /**
     * Layout 2026: tanpa FUEL — CC kosong + kWh di TANK → BEV.
     * Unit terparse 38.915 vs resmi 42.465 → coverage ~0.916.
     */
    protected function writeFixture2026(string $path): void
    {
        $sheet = 'Table 1';
        $rows = [
            ['NO', 'BRAND', 'TYPE', 'CC', 'TANK', 'JAN', 'FEB', 'MAR', 'APR', 'TOTAL'],
            ['SEDAN'],
            [1, 'TOYOTA', 'Veloz 1.5 V HEV A/T', '1500', '45 L', 10.512, 20103, 5025, 1000, 36640],
            [2, 'BYD', 'Atto 1 Dynamic', '-', '51.8 kWh', 300, 100, 50, 25, 475],
            [3, 'WULING', 'Air EV', '-', '', 200, 150, 100, 50, 500],
            [4, 'HONDA', 'Brio Satya E', '1200', '40 L', 500, 400, 300, 100, 1300],
            ['TOTAL', null, null, null, null, 11512, 20753, 5475, 1175, 38915],
            [],
            ['DOMESTIC SALES TOTAL'],
            [null, null, null, null, null, 12512, 21103, 6425, 2225, 42465],
        ];

        $this->writeXlsx($path, $sheet, $rows);
    }

    /**
     * Layout 2022/2023: kolom FUEL eksplisit + alias brand.
     */
    protected function writeFixtureFuelColumn(string $path): void
    {
        $sheet = 'Table 1';
        $rows = [
            ['NO', 'BRAND', 'TYPE', 'FUEL', 'JAN', 'FEB', 'MAR', 'APR', 'TOTAL'],
            ['SEDAN'],
            [1, 'HYUNDAI - HMID', 'Ioniq5', 'EV', 100, 120, 80, 60, 360],
            [2, 'HONDA', 'Brio Satya E', 'BENSIN', 500, 400, 300, 100, 1300],
            ['TOTAL', null, null, null, 600, 520, 380, 160, 1660],
            [],
            ['DOMESTIC SALES TOTAL'],
            [null, null, null, null, 600, 520, 380, 160, 1660],
        ];

        $this->writeXlsx($path, $sheet, $rows);
    }

    protected function writeXlsx(string $path, string $sheetTitle, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                if ($value !== null) {
                    $sheet->getCell([$c + 1, $r + 1])->setValue($value);
                }
            }
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }
}
