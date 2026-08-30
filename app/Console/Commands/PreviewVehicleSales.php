<?php

namespace App\Console\Commands;

use App\Models\TypeVehicle;
use App\Services\VehicleNameSplitter;
use App\Services\VehicleSalesMatcher;
use Illuminate\Console\Command;

/**
 * PREVIEW (dry-run, read-only) sebelum import laporan penjualan bulanan/
 * tahunan GAIKINDO: parser yang sama dgn vehicle-sales:import-csv, tapi
 * TIDAK menulis apa pun. Menjawab "apa yang baru di laporan ini?" terhadap
 * katalog base: kombinasi raw brand/model yang belum ter-match dilaporkan
 * + saran kecocokan terdekat, supaya bisa diputuskan dulu (tambah alias,
 * masukkan ke CONNECTING → impor hierarki) SEBELUM data masuk stats.
 *
 * Opsi --export-new= menulis kombinasi baru sebagai CSV siap gabung ke
 * CONNECTING (kolom BRAND, MODEL, TYPE, POWERTRAIN, CATEGORY, SIZE kosong).
 *
 * Contoh:
 *   php artisan vehicle-sales:preview docs/csv/GAIKINDO_2026-01.csv --month=1
 *   php artisan vehicle-sales:preview docs/csv/GAIKINDO_2026.csv --export-new=/tmp/baru.csv
 */
class PreviewVehicleSales extends ImportVehicleSalesCsv
{
    protected $signature = 'vehicle-sales:preview
        {file : Path ke file CSV GAIKINDO}
        {--year= : Tahun periode (default: dideteksi dari nama file)}
        {--month= : Bulan periode 1-12 (kosong = file tahunan JAN..DEC)}
        {--export-new= : Path output CSV kombinasi baru (opsional)}';

    protected $description = 'Preview laporan penjualan vs katalog base — tanpa menulis apa pun.';

    public function handle(VehicleNameSplitter $splitter, VehicleSalesMatcher $matcher): int
    {
        $filePath = (string) $this->argument('file');

        if (! is_file($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $year = $this->resolveYear($filePath);

        if ($year === null) {
            $this->error('Tahun tidak diketahui: berikan --year atau sisipkan 4 digit tahun pada nama file.');

            return self::FAILURE;
        }

        $month = null;

        if ($this->option('month') !== null && $this->option('month') !== '') {
            $month = (int) $this->option('month');

            if ($month < 1 || $month > 12) {
                $this->error('--month harus angka 1-12.');

                return self::FAILURE;
            }
        }

        $parsed = $this->readCsv($filePath, $month);

        if ($parsed === null) {
            return self::FAILURE;
        }

        [$rows, $junkSkipped] = $parsed;

        /** @var array<string, array{brand: string, model: string, type: string, powertrain: string, units: int, suggestion: ?string}> $new */
        $new = [];
        $matched = 0;
        $nonBev = 0;
        $skipped = $junkSkipped;

        foreach ($rows as $row) {
            $split = $this->splitter()->split($row['brand'], $row['type_model'], $row['fuel']);

            if ($split['flag'] === 'junk' || $split['model'] === '') {
                $skipped++;

                continue;
            }

            // Katalog base khusus BEV (aturan yang sama dgn import) — baris
            // non-BEV memang tidak ter-link, jadi bukan "data baru".
            if ($split['powertrain'] !== 'BEV') {
                $nonBev++;

                continue;
            }

            $preview = $matcher->preview($row['brand'], $split['model']);

            if (! $preview['brand_new'] && ! $preview['model_new']) {
                $matched++;

                continue;
            }

            $key = strtoupper($row['brand']).'|'.strtoupper($split['model']);
            $units = $this->rowUnits($row, $month);

            if (isset($new[$key])) {
                $new[$key]['units'] += $units;
            } else {
                $new[$key] = [
                    'brand' => $row['brand'],
                    'model' => $split['model'],
                    'type' => $split['type'],
                    'powertrain' => $split['powertrain'],
                    'units' => $units,
                    'suggestion' => $preview['brand_name'] ?? null,
                ];
            }
        }

        // ---- Ringkasan ----
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['File', basename($filePath)],
                ['Periode', $month === null ? "tahunan {$year}" : "bulan {$month} tahun {$year}"],
                ['Baris dibaca', count($rows)],
                ['Baris dilewati (junk/kosong)', $skipped],
                ['Baris non-BEV (tidak ter-link katalog, by design)', $nonBev],
                ['Kombinasi BEV ter-match ke katalog', $matched],
                ['Kombinasi BARU (perlu keputusan)', count($new)],
            ]
        );

        if ($new === []) {
            $this->info('✓ Semua kombinasi BEV sudah ter-match ke katalog — aman diimpor.');

            return self::SUCCESS;
        }

        $this->warn('Kombinasi BARU terhadap katalog base (belum ada di brand/model):');
        $this->table(
            ['Brand', 'Model', 'Type', 'Units', 'Brand Katalog'],
            array_map(fn ($n) => [$n['brand'], $n['model'], $n['type'] ?: '-', $n['units'], $n['suggestion'] ?? '— (brand baru)'], array_values($new)),
        );

        $this->line('Langkah selanjutnya:');
        $this->line('  a. Varian dari model existing → tambahkan alias / perbaiki CONNECTING');
        $this->line('  b. Benar-benar baru → masukkan ke CONNECTING + isi kategori → impor hierarki (Filament) → baru import asli');
        $this->line('  c. Atau eksport sebagai CSV: --export-new=path.csv');

        if (($export = $this->option('export-new')) !== null) {
            $handle = fopen($export, 'w');
            fputcsv($handle, ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);

            foreach ($new as $n) {
                fputcsv($handle, [$n['brand'], $n['model'], $n['type'], $n['powertrain'], '', '']);
            }

            fclose($handle);
            $this->info("✓ ".count($new)." kombinasi baru ditulis ke {$export} (kolom CATEGORY/SIZE diisi manual).");
        }

        return self::SUCCESS;
    }

    /** Splitter dipakai ulang dari parent — instance via container. */
    protected function splitter(): VehicleNameSplitter
    {
        return app(VehicleNameSplitter::class);
    }

    protected function rowUnits(array $row, ?int $month): int
    {
        if ($month !== null) {
            return $row['units'] ?? $row['cells'][$month] ?? 0;
        }

        return array_sum($row['cells']);
    }

    /** Cek type existing di bawah model (read-only) — dipakai bila perlu. */
    protected function typeExists(int $modelId, string $typeName): bool
    {
        return TypeVehicle::query()
            ->where('model_vehicle_id', $modelId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($typeName))])
            ->exists();
    }
}
