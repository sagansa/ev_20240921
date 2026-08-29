<?php

namespace App\Console\Commands;

use App\Services\VehicleCategoryAssigner;
use Illuminate\Console\Command;

/**
 * Mengisi CATEGORY + SIZE (dan membersihkan POWERTRAIN) pada CONNECTING CSV
 * GAIKINDO yang sudah dikontaminasi formula Excel ("=AI(...)", nilai FUEL
 * satu huruf, baris sampah AI).
 *
 * Output:
 *  - CSV bersih: BRAND,MODEL,TYPE,POWERTRAIN,CATEGORY,SIZE (default
 *    docs/csv/GAIKINDO_CONNECTING.csv ditimpa).
 *  - CSV review: baris low-confidence (kategori tak terpetakan) + konflik
 *    powertrain untuk direview manusia.
 *
 * Contoh: php artisan vehicle-category:assign docs/csv/GAIKINDO_CONNECTING.csv
 */
class AssignVehicleCategories extends Command
{
    protected $signature = 'vehicle-category:assign
        {csv : File CONNECTING CSV input}
        {--out= : Path output bersih (default: timpa file input)}
        {--review= : Path CSV review (default: docs/csv/CATEGORY_REVIEW.csv)}';

    protected $description = 'Isi kategori/ukuran + bersihkan powertrain pada CONNECTING CSV GAIKINDO';

    public function handle(VehicleCategoryAssigner $assigner): int
    {
        $in = $this->argument('csv');

        if (! is_file($in)) {
            $this->error("File tidak ada: {$in}");

            return self::FAILURE;
        }

        $out = $this->option('out') ?: $in;
        $review = $this->option('review') ?: dirname($in).'/CATEGORY_REVIEW.csv';

        $stats = ['clean' => 0, 'junk' => 0, 'review' => 0, 'ptUnknown' => 0];
        $seen = [];
        $rows = [];
        $reviewRows = [];

        // BACA SEMUA baris dulu, baru buka file output — jangan pernah
        // fopen(..., 'w') sebelum selesai membaca (input bisa = output).
        $handle = fopen($in, 'r');
        if ($handle === false) {
            $this->error("Gagal membuka: {$in}");

            return self::FAILURE;
        }

        // Header file bisa "BRAND MODEL TYPE,FUEL,BRAND,MODEL,TYPE,POWERTRAIN,
        // CATEGORY" (artefak kolom pertama gabungan) — petakan posisi kolom
        // dari header baris pertama yang mengandung "BRAND".
        $header = fgetcsv($handle);
        $col = $this->mapColumns($header ?: []);
        if ($col === null) {
            $this->error('Header tidak dikenali: '.implode(',', $header ?: []));
            fclose($handle);

            return self::FAILURE;
        }

        $rows = [];
        while (($raw = fgetcsv($handle)) !== false) {
            if (count($raw) === 1 && trim((string) $raw[0]) === '') {
                continue;
            }

            $brand = trim((string) ($raw[$col['brand']] ?? ''));
            $model = trim((string) ($raw[$col['model']] ?? ''));
            $type = trim((string) ($raw[$col['type']] ?? ''));
            $fuel = trim((string) ($raw[$col['fuel']] ?? ''));

            // Baris sampah artefak formula AI / kolom bergeser.
            if ($this->isJunk($brand, $model)) {
                $stats['junk']++;

                continue;
            }

            $assigned = $assigner->assign($brand, $model, $type, $fuel, $raw[$col['powertrain']] ?? null);

            $key = strtoupper($brand).'|'.strtoupper($model).'|'.strtoupper($type);
            if (isset($seen[$key])) {
                continue; // duplikat persis — CSV hanya butuh satu baris.
            }
            $seen[$key] = true;

            if ($assigned['powertrain'] === null) {
                $stats['ptUnknown']++;
            }

            $row = [$brand, $model, $type, $assigned['powertrain'] ?? '', $assigned['category'] ?? '', $assigned['size'] ?? ''];

            $reasons = [];
            if ($assigned['category'] === null) {
                $reasons[] = 'kategori-perlu-review';
            }
            if ($assigned['powertrain'] === null) {
                $reasons[] = 'powertrain-tak-diketahui';
            }

            if ($reasons !== []) {
                $stats['review']++;
                $reviewRows[] = [...$row, implode(';', $reasons)];
            } else {
                $stats['clean']++;
            }

            $rows[] = $row;
        }

        fclose($handle);

        $outHandle = fopen($out, 'w');
        $reviewHandle = fopen($review, 'w');
        fputcsv($outHandle, ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);
        fputcsv($reviewHandle, ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE', 'REASON']);
        foreach ($rows as $row) {
            fputcsv($outHandle, $row);
        }
        foreach ($reviewRows as $row) {
            fputcsv($reviewHandle, $row);
        }
        fclose($outHandle);
        fclose($reviewHandle);

        $this->info("Bersih: {$stats['clean']} baris → {$out}");
        $this->info("Junk dibuang: {$stats['junk']}");
        $this->info("Perlu review: {$stats['review']} → {$review}");
        $this->info("Powertrain tak diketahui: {$stats['ptUnknown']}");

        return self::SUCCESS;
    }

    /** @param array<int, string> $header */
    protected function mapColumns(array $header): ?array
    {
        // Cari kolom: pass exact-match dulu (header kolom pertama sering
        // gabungan "BRAND MODEL TYPE" yang mengandung semua kata kunci),
        // lalu pass contains.
        $find = function (string $needle, array $header): ?int {
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

        $brand = $find('BRAND', $header);
        $model = $find('MODEL', $header);

        if ($brand === null || $model === null) {
            return null;
        }

        return [
            'brand' => $brand,
            'model' => $model,
            // TYPE: kolom kedua bernama TYPE (bukan "BRAND MODEL TYPE").
            'type' => $find('TYPE', $header) ?? $model,
            'fuel' => $find('FUEL', $header) ?? 1,
            'powertrain' => $find('POWERTRAIN', $header) ?? 5,
        ];
    }

    protected function isJunk(string $brand, string $model): bool
    {
        $cells = strtoupper($brand.' '.$model);

        // Artefak formula AI dari Excel.
        if (str_contains($cells, '=AI(') || str_contains($cells, 'SOMETHING WENT WRONG')) {
            return true;
        }

        // Kolom bergeser total: brand "G" berisi sisa nilai FUEL.
        if (in_array(trim($brand), ['G', 'D'], true)) {
            return true;
        }

        return false;
    }
}
