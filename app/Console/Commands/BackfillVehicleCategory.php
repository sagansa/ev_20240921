<?php

namespace App\Console\Commands;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Support\VehicleCategories;
use Illuminate\Console\Command;

/**
 * Mengisi category/size_class pada model_vehicles EXISTING dari CSV mapping
 * (hasil vehicle-category:assign). CSV sumber kebenaran: nilai model yang
 * sudah terisi tetap ditimpa bila CSV menyediakan nilai — idempoten, aman
 * dijalankan ulang setelah koreksi CSV.
 *
 * Contoh: php artisan vehicle-hierarchy:backfill-category docs/csv/GAIKINDO_CONNECTING.csv
 */
class BackfillVehicleCategory extends Command
{
    protected $signature = 'vehicle-hierarchy:backfill-category
        {csv : File CSV mapping (kolom BRAND, MODEL, CATEGORY, SIZE)}';

    protected $description = 'Backfill kategori & ukuran model kendaraan existing dari CSV mapping';

    public function handle(): int
    {
        $csv = $this->argument('csv');

        if (! is_file($csv)) {
            $this->error("File tidak ada: {$csv}");

            return self::FAILURE;
        }

        $handle = fopen($csv, 'r');
        if ($handle === false) {
            $this->error("Gagal membuka: {$csv}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        $col = $this->mapColumns($header ?: []);
        if ($col === null) {
            $this->error('Header tidak dikenali: '.implode(',', $header ?: []));
            fclose($handle);

            return self::FAILURE;
        }

        $updated = 0;
        $skippedNoCategory = 0;
        $notFound = [];
        $seen = [];

        while (($raw = fgetcsv($handle)) !== false) {
            if (count($raw) === 1 && trim((string) $raw[0]) === '') {
                continue;
            }

            $brandName = trim((string) ($raw[$col['brand']] ?? ''));
            $modelName = trim((string) ($raw[$col['model']] ?? ''));
            $category = VehicleCategories::normalizeCategory($raw[$col['category']] ?? null);
            $sizeClass = VehicleCategories::normalizeSize($raw[$col['size']] ?? null);

            if ($brandName === '' || $modelName === '') {
                continue;
            }

            $key = strtolower($brandName).'|'.strtolower($modelName);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($category === null && $sizeClass === null) {
                $skippedNoCategory++;

                continue;
            }

            $model = ModelVehicle::query()
                ->select('model_vehicles.*')
                ->join('brand_vehicles', 'brand_vehicles.id', '=', 'model_vehicles.brand_vehicle_id')
                ->whereRaw('LOWER(brand_vehicles.name) = ?', [strtolower($brandName)])
                ->whereRaw('LOWER(model_vehicles.name) = ?', [strtolower($modelName)])
                ->first();

            if ($model === null) {
                $notFound[] = "$brandName $modelName";

                continue;
            }

            $attributes = [];
            if ($category !== null) {
                $attributes['category'] = $category;
            }
            if ($sizeClass !== null) {
                $attributes['size_class'] = $sizeClass;
            }

            if ($model->fill($attributes)->isDirty()) {
                $model->save();
                $updated++;
            }
        }

        fclose($handle);

        $this->info("Model diperbarui: {$updated}");
        $this->info("Baris CSV tanpa kategori (dilewati): {$skippedNoCategory}");
        $this->info('Model DB tidak ada di CSV: '.count($notFound));

        if ($notFound !== []) {
            $this->warn('  contoh: '.implode('; ', array_slice($notFound, 0, 10)));
        }

        return self::SUCCESS;
    }

    /** @param array<int, string> $header */
    protected function mapColumns(array $header): ?array
    {
        $find = function (string $needle) use ($header): ?int {
            foreach ([true, false] as $exact) {
                foreach ($header as $i => $name) {
                    $name = strtoupper(trim((string) $name));

                    if ($name === '') {
                        continue;
                    }

                    if ($exact ? $name === $needle : str_contains($name, $needle)) {
                        return (int) $i;
                    }
                }
            }

            return null;
        };

        $brand = $find('BRAND');
        $model = $find('MODEL');

        if ($brand === null || $model === null) {
            return null;
        }

        return [
            'brand' => $brand,
            'model' => $model,
            'category' => $find('CATEGORY'),
            'size' => $find('SIZE'),
        ];
    }
}
