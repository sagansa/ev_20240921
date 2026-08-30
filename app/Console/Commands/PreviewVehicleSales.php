<?php

namespace App\Console\Commands;

use App\Services\VehicleSalesPreviewService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * PREVIEW (dry-run, read-only) sebelum import laporan penjualan bulanan/
 * tahunan GAIKINDO — versi CLI dari halaman admin "Preview Impor Penjualan".
 * Analisis di VehicleSalesPreviewService (dipakai bersama GUI).
 *
 * Contoh:
 *   php artisan vehicle-sales:preview docs/csv/GAIKINDO_2026.csv --export-new=/tmp/baru.csv
 */
class PreviewVehicleSales extends Command
{
    protected $signature = 'vehicle-sales:preview
        {file : Path ke file CSV GAIKINDO}
        {--year= : Tahun periode (default: dideteksi dari nama file)}
        {--month= : Bulan periode 1-12 (kosong = file tahunan JAN..DEC)}
        {--export-new= : Path output CSV kombinasi baru (opsional)}';

    protected $description = 'Preview laporan penjualan vs katalog base — tanpa menulis apa pun.';

    public function handle(VehicleSalesPreviewService $previewService): int
    {
        $filePath = (string) $this->argument('file');

        if (! is_file($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

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

        try {
            $result = $previewService->analyze($filePath, $month);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $s = $result['summary'];
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['File', basename($filePath)],
                ['Periode', $month === null ? 'tahunan' : "bulan {$month}"],
                ['Baris dibaca', $s['rows']],
                ['Baris dilewati (junk/kosong)', $s['skipped']],
                ['Baris non-BEV (tidak ter-link katalog, by design)', $s['nonBev']],
                ['Kombinasi BEV ter-match ke katalog', $s['matched']],
                ['Kombinasi BARU (perlu keputusan)', $s['new']],
            ]
        );

        if ($result['new'] === []) {
            $this->info('✓ Semua kombinasi BEV sudah ter-match ke katalog — aman diimpor.');

            return self::SUCCESS;
        }

        $this->warn('Kombinasi BARU terhadap katalog base (belum ada di brand/model):');
        $this->table(
            ['Brand', 'Model', 'Type', 'Units', 'Brand Katalog'],
            array_map(fn ($n) => [$n['brand'], $n['model'], $n['type'] ?: '-', $n['units'], $n['brand_name'] ?? '— (brand baru)'], $result['new']),
        );

        $this->line('Langkah selanjutnya:');
        $this->line('  a. Varian dari model existing → tambahkan alias / perbaiki CONNECTING');
        $this->line('  b. Benar-benar baru → masukkan ke CONNECTING + isi kategori → impor hierarki (Filament) → baru import asli');
        $this->line('  c. Atau eksport sebagai CSV: --export-new=path.csv');

        if (($export = $this->option('export-new')) !== null) {
            $count = $previewService->exportNewCsv($result['new'], $export);
            $this->info("✓ {$count} kombinasi baru ditulis ke {$export} (kolom CATEGORY/SIZE diisi manual).");
        }

        return self::SUCCESS;
    }
}
