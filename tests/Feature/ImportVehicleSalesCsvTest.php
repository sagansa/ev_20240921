<?php

namespace Tests\Feature;

use App\Models\ModelVehicle;
use App\Models\SalesImport;
use App\Models\TypeVehicle;
use App\Models\VehicleSalesStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportVehicleSalesCsvTest extends TestCase
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

    /** @param list<string> $header @param list<list<string>> $rows */
    private function writeCsv(array $header, array $rows): string
    {
        $dir = storage_path('framework/testing/import-vehicle-sales-csv');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid('gaikindo-', true).'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $header);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_yearly_import_creates_monthly_and_annual_rows_with_catalog_links(): void
    {
        $path = $this->writeCsv(
            ['BRAND', 'TYPE MODEL', 'CC', 'TRANS', 'FUEL', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC', 'TOTAL'],
            [
                ['TOYOTA', 'Agya 1.2 G AT', '1200', 'AT', 'G', '13', '-', '', '', '', '', '', '', '', '', '', '', '17'],
                ['HYUNDAI', 'Ioniq EV Prime', '', '', 'BEV', '0', '0', '0', '0', '0', '0', '0', '0', '4', '0', '0', '0', '4'],
                ['TOTAL', 'CUMULATIVE', '', '', '', '13', '4', '', '', '', '', '', '', '', '', '', '', '17'],
            ],
        );

        $this->artisan('vehicle-sales:import-csv', [
            'file' => $path,
            '--year' => '2022',
        ])->assertSuccessful();

        // 2 baris data × (1 bulan bernilai > 0 + 1 agregat tahunan); baris TOTAL/CUMULATIVE dilewati.
        $this->assertSame(4, VehicleSalesStat::count());

        $jan = VehicleSalesStat::query()->where('month', 1)->firstOrFail();
        $this->assertSame(13, (int) $jan->units);
        $this->assertSame('TOYOTA', $jan->raw_brand);
        $this->assertSame('Agya 1.2 G AT', $jan->raw_model);
        $this->assertSame(2022, (int) $jan->year);
        // ATURAN BEV-ONLY: baris ICE tidak membuat/menaut katalog.
        $this->assertNull($jan->brand_vehicle_id);
        $this->assertNull($jan->model_vehicle_id);
        $this->assertNull($jan->type_vehicle_id);
        $this->assertSame('ICE', $jan->powertrain);

        $sep = VehicleSalesStat::query()->where('month', 9)->firstOrFail();
        $this->assertSame(4, (int) $sep->units);
        $this->assertSame('HYUNDAI', $sep->raw_brand);
        $this->assertNotNull($sep->brand_vehicle_id);
        $this->assertNotNull($sep->model_vehicle_id);
        $this->assertNotNull($sep->type_vehicle_id);
        $this->assertSame('BEV', $sep->powertrain);

        $annualUnits = VehicleSalesStat::query()
            ->whereNull('month')
            ->orderBy('units')
            ->pluck('units')
            ->map(fn ($units) => (int) $units)
            ->all();
        $this->assertSame([4, 13], $annualUnits);

        // Type hanya dibuat untuk baris BEV.
        $this->assertSame(0, TypeVehicle::query()->where('name', 'Agya 1.2 G AT')->count());
        $this->assertSame(1, TypeVehicle::count());

        $ioniqType = TypeVehicle::query()->where('name', 'Ioniq EV Prime')->firstOrFail();
        $this->assertSame('Ioniq', $ioniqType->modelVehicle->name);
    }

    public function test_monthly_import_replaces_instead_of_duplicating(): void
    {
        $path = $this->writeCsv(
            ['BRAND', 'TYPE MODEL', 'FUEL', 'UNITS'],
            [
                ['TOYOTA', 'Agya 1.2 G AT', 'G', '8'],
            ],
        );

        $options = ['file' => $path, '--year' => '2026', '--month' => '1'];

        $this->artisan('vehicle-sales:import-csv', $options)->assertSuccessful();

        $countAfterFirst = VehicleSalesStat::query()->where('year', 2026)->where('month', 1)->count();
        $this->assertSame(1, $countAfterFirst);

        $this->artisan('vehicle-sales:import-csv', $options)->assertSuccessful();

        $countAfterSecond = VehicleSalesStat::query()->where('year', 2026)->where('month', 1)->count();
        $this->assertSame($countAfterFirst, $countAfterSecond);
        $this->assertSame(1, VehicleSalesStat::count());
        $this->assertSame(2, SalesImport::count());
    }

    public function test_splitter_groups_marketing_prefix_into_type_not_model(): void
    {
        $path = $this->writeCsv(
            ['BRAND', 'TYPE MODEL', 'CC', 'TRANS', 'FUEL', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC', 'TOTAL'],
            [
                ['BYD', 'All New Seal Premium', '', '', 'BEV', '5', '', '', '', '', '', '', '', '', '', '', '', '5'],
            ],
        );

        $this->artisan('vehicle-sales:import-csv', [
            'file' => $path,
            '--year' => '2023',
        ])->assertSuccessful();

        $model = ModelVehicle::query()->where('name', 'Seal')->firstOrFail();

        $type = TypeVehicle::query()
            ->where('model_vehicle_id', $model->id)
            ->where('name', 'All New Seal Premium')
            ->firstOrFail();

        $stat = VehicleSalesStat::query()->whereNull('month')->firstOrFail();
        $this->assertSame($model->id, (int) $stat->model_vehicle_id);
        $this->assertSame($type->id, (int) $stat->type_vehicle_id);
        $this->assertSame('All New Seal Premium', $stat->raw_model);
        $this->assertSame(5, (int) $stat->units);
    }
}
