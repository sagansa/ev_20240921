<?php

namespace App\Console\Commands;

use App\Services\GaikindoImportService;
use App\Services\GaikindoPdfImportService;
use Illuminate\Console\Command;

class ImportVehicleSales extends Command
{
    protected $signature = 'vehicle-sales:import
                            {file : Path ke file xlsx/pdf wholesales GAIKINDO}
                            {--year= : Tahun periode (default: dideteksi dari nama file)}
                            {--source=gaikindo : Sumber data}';

    protected $description = 'Import file wholesales GAIKINDO (xlsx native ATAU pdf asli) ke sales_imports + vehicle_sales_stats, lalu fuzzy-match ke katalog brand/model/type kendaraan.';

    public function handle(GaikindoImportService $xlsxService): int
    {
        // xlsx GAIKINDO besar (ribuan baris × kolom) — naikkan memory CLI one-shot.
        ini_set('memory_limit', '512M');

        $filePath = (string) $this->argument('file');
        $year = $this->option('year') !== null ? (int) $this->option('year') : null;

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return 1;
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
            // PDF cetak GAIKINDO: header bulan terjalin huruf-per-huruf antar
            // sub-baris & grid vertikal tak kontinu → rekonstruksi faithful
            // butuh parser font-metrik khusus (lihat spec & skrip v3 di
            // scripts/gaikindo_pdf_rows.py). Sengaja ditolak agar undercount
            // tidak masuk DB; gunakan Excel native GAIKINDO.
            $this->error('Import PDF asli belum didukung: layout cetak berlapis menghasilkan data tidak reliable.');
            $this->line('Gunakan file Excel native GAIKINDO (pola 3_GAIKINDO_wholesales_data_*.xlsx).');

            return 1;
        }

        $this->info('Mengimport: '.basename($filePath).' (XLSX)'.($year !== null ? " (tahun {$year})" : ''));

        try {
            $summary = $xlsxService->importFromFile($filePath, $year, (string) $this->option('source'));
        } catch (\Throwable $e) {
            $this->error('Import gagal: '.$e->getMessage());

            return 1;
        }

        $this->newLine();
        $this->info('✓ Import berhasil!');
        $coverage = $summary['coverage'] !== null ? sprintf('%.1f%%', $summary['coverage'] * 100) : 'n/a';
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['Import ID', $summary['import_id']],
                ['Tahun', $summary['year']],
                ['Status', $summary['status']],
                ['Baris model terparse', $summary['model_rows']],
                ['Baris stats tersimpan', $summary['stat_rows']],
                ['Total unit terparse', number_format($summary['parsed_total'])],
                ['Total resmi (DOMESTIC)', $summary['official_total'] !== null ? number_format($summary['official_total']) : 'n/a'],
                ['Coverage', $coverage],
                ['Brand baru dibuat', $summary['matcher']['created_brands']],
                ['Model baru dibuat', $summary['matcher']['created_models']],
                ['Type (baterai) baru dibuat', $summary['matcher']['created_types']],
                ['Model BEV terdeteksi', $summary['matcher']['bev_models']],
            ]
        );

        foreach ($summary['warnings'] as $warning) {
            $this->warn('⚠ '.$warning);
        }

        return 0;
    }
}
