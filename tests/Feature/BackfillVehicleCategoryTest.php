<?php

namespace Tests\Feature;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillVehicleCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $path, array $rows): string
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }

    public function test_backfills_category_and_size_by_case_insensitive_match(): void
    {
        $toyota = BrandVehicle::create(['name' => 'TOYOTA']);
        ModelVehicle::create(['name' => 'Avanza', 'brand_vehicle_id' => $toyota->id]);
        ModelVehicle::create(['name' => 'AVANZA', 'brand_vehicle_id' => $toyota->id]);

        $csv = $this->writeCsv(
            storage_path('app/backfill-test.csv'),
            [['Toyota', 'avanza', '1.5 G', 'ICE', 'mpv', 'small']],
        );

        $this->artisan('vehicle-hierarchy:backfill-category', ['csv' => $csv])
            ->expectsOutputToContain('Model diperbarui: 1')
            ->assertSuccessful();

        // Kapitalisasi pertama yang menang: model "Avanza" terisi, "AVANZA" tidak.
        $this->assertSame('MPV', ModelVehicle::where('name', 'Avanza')->value('category'));
        $this->assertSame('Small', ModelVehicle::where('name', 'Avanza')->value('size_class'));
        $this->assertNull(ModelVehicle::where('name', 'AVANZA')->value('category'));
    }

    public function test_is_idempotent_and_overwrites_with_csv_values(): void
    {
        $byd = BrandVehicle::create(['name' => 'BYD']);
        $model = ModelVehicle::create([
            'name' => 'Seal', 'brand_vehicle_id' => $byd->id,
            'category' => 'Hatchback',
        ]);

        $csv = $this->writeCsv(storage_path('app/backfill-test-2.csv'), [
            ['BYD', 'Seal', '', 'BEV', 'Sedan', 'Medium'],
        ]);

        $this->artisan('vehicle-hierarchy:backfill-category', ['csv' => $csv])->assertSuccessful();

        $model->refresh();
        $this->assertSame('Sedan', $model->category);
        $this->assertSame('Medium', $model->size_class);
    }

    public function test_rows_without_category_are_skipped_and_missing_models_reported(): void
    {
        $byd = BrandVehicle::create(['name' => 'BYD']);
        ModelVehicle::create(['name' => 'Seal', 'brand_vehicle_id' => $byd->id]);

        $csv = $this->writeCsv(storage_path('app/backfill-test-3.csv'), [
            ['HINO', '115LD', '', 'ICE', '', ''],
            ['BRAND BARU', 'Model Misterius', '', '', 'SUV', ''],
        ]);

        $this->artisan('vehicle-hierarchy:backfill-category', ['csv' => $csv])
            ->expectsOutputToContain('Model diperbarui: 0')
            ->expectsOutputToContain('Model DB tidak ada di CSV: 1')
            ->assertSuccessful();

        $this->assertNull(ModelVehicle::where('name', 'Seal')->value('category'));
    }
}
