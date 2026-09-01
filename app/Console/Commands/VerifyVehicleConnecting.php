<?php

namespace App\Console\Commands;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Services\VehicleSalesMatcher;
use App\Services\VehicleConnectingComparer;
use Illuminate\Console\Command;

/**
 * Pembanding CONNECTING vs katalog di DB — menjawab "apa yang berubah?"
 * sebelum melakukan impor/backfill, terutama setelah rename di CSV.
 *
 * Read-only. Mengelompokkan perbedaan per AKSI yang harus dilakukan:
 *  - BRAND BARU           : brand belum ada di DB → akan dibuat oleh Import
 *  - MODEL BARU           : brand ada, model belum → akan dibuat oleh Import
 *  - TYPE BARU            : brand+model ada, type belum → akan dibuat oleh Import
 *  - KLASIFIKASI BEDA     : entitas ada, powertrain/category/size beda → Import/backfill
 *  - DI DB, TIDAK ADA DI CSV : entitas DB tak direferensikan CSV — kandidat
 *                            rename (CSV dianggap sumber) atau memang di luar
 *                            cakupan CONNECTING; tinjau manual sebelum menghapus.
 *
 * Contoh: php artisan vehicle-connecting:verify docs/csv/GAIKINDO_CONNECTING.csv
 */
class VerifyVehicleConnecting extends Command
{
    protected $signature = 'vehicle-connecting:verify
        {csv : Path file CONNECTING CSV}
        {--json= : Path output laporan lengkap dalam JSON (opsional)}';

    protected $description = 'Bandingkan CONNECTING CSV vs katalog DB — apa yang baru/beda/renamed';

    public function handle(): int
    {
        $csv = (string) $this->argument('csv');

        if (! is_file($csv)) {
            $this->error("File tidak ada: {$csv}");

            return self::FAILURE;
        }

        $handle = fopen($csv, 'r');
        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        foreach (['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE'] as $need) {
            if (! in_array($need, $hdr, true)) {
                $this->error("Header wajib memuat kolom: {$need}");

                return self::FAILURE;
            }
        }
        $idx = fn (string $n): int => (int) array_search($n, $hdr, true);

        $report = app(VehicleConnectingComparer::class)->compare($csv);

        // ---- Tampilkan ----
        $this->renderSection('✓ Sama', count($report['match']), 'baris cocok penuh');
        $this->renderSection('BRAND BARU (akan dibuat oleh Import)', count($report['brandBaru']), null, array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['category'] ?: '-'], $report['brandBaru']));
        $this->renderSection('MODEL BARU (brand ada, model belum)', count($report['modelBaru']), null, array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['category'] ?: '-'], $report['modelBaru']));
        $this->renderSection('TYPE BARU (brand+model ada)', count($report['typeBaru']), null, array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['type']], $report['typeBaru']));
        $this->renderSection('KLASIFIKASI BEDA (perlu Import/backfill)', count($report['klasifikasiBeda']), null, array_values(array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['diff']], $report['klasifikasiBeda'])));
        $this->renderSection('DI DB, TIDAK ADA DI CSV (tinjau: kandidat rename/hapus)', count($report['dbBrandTanpaCsv']), null, array_map(
            fn ($r) => [$r['brand']], $report['dbBrandTanpaCsv']));
        $this->renderSection('CSV TIDAK KONSISTEN (varian campuran — informasi)', count($report['csvTidakKonsisten']), null, array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['detail']], $report['csvTidakKonsisten']));
        $this->renderSection('MODEL DB TIDAK ADA DI CSV', count($report['dbModelTanpaCsv']), null, array_map(
            fn ($r) => [$r['brand'], $r['model'], $r['category'] ?: '-'], array_slice($report['dbModelTanpaCsv'], 0, 25)));

        if (($json = $this->option('json')) !== null) {
            file_put_contents($json, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Laporan lengkap: {$json}");
        }

        $this->line('Aksi: klasifikasi beda → Import/backfill · entity baru → Import · DB tak ada di CSV → tinjau (rename via admin, jangan lewat CSV).');

        return self::SUCCESS;
    }

    protected function renderSection(string $title, int $count, ?string $subtitle, array $rows = []): void
    {
        $color = str_starts_with($title, '✓') ? 'info' : 'comment';
        $this->$color("{$title}: {$count}".($subtitle !== null ? " {$subtitle}" : ''));

        if ($rows !== []) {
            $this->table(['Kolom 1', 'Kolom 2', 'Kolom 3'], array_map(
                fn ($r) => array_pad(array_slice($r, 0, 3), 3, ''), array_slice($rows, 0, 20)));
            if (count($rows) > 20) {
                $this->line('  … +'.(count($rows) - 20).' lainnya');
            }
        }
    }
}
