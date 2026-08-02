<?php

namespace App\Console\Commands;

use App\Services\EsdmSinggatImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportEsdmSinggat extends Command
{
    protected $signature = 'esdm:import-singgat
                            {file? : Path ke JSON (default: ../data/esdm_singgat_lokasi.json)}
                            {--fetch : Fetch ulang JSON langsung dari API ESDM sebelum import}
                            {--append : Jangan hapus data lama (default: replace)}
                            {--spklu-only : Hanya import SPKLU (mobil), skip SPBKLU}';

    protected $description = 'Import data ESDM Singgat (gatrik.esdm.go.id) ke tabel esdm_singgat_*. Pipeline terpisah dari spklu_locations & spklu_scrape_raw.';

    private const API_URL = 'https://gatrik.esdm.go.id/singgat/api/api/get-lokasi';

    public function handle(EsdmSinggatImportService $service): int
    {
        // JSON ESDM 72MB dengan base64 inline → decode ~170MB di memori PHP.
        // Naikkan dari default 128M agar aman; ini command one-shot CLI.
        ini_set('memory_limit', '512M');

        $filePath = $this->argument('file') ?? base_path('../data/esdm_singgat_lokasi.json');

        if ($this->option('fetch')) {
            $filePath = $this->fetchFresh($filePath);
            if ($filePath === null) {
                return 1;
            }
        }

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            $this->info('Tip: jalankan dengan --fetch untuk download otomatis dari API ESDM.');
            return 1;
        }

        $replace = ! $this->option('append');
        $withSpbklu = ! $this->option('spklu-only');

        if ($replace) {
            $this->warn('MODE REPLACE — Semua data esdm_singgat_* akan dihapus sebelum import.');
        } else {
            $this->info('MODE APPEND — Data lama dipertahankan (bisa duplikat esdm_id, akan gagal krn unique).');
            $this->warn('Catatan: opsi --append biasanya hanya cocok bila tabel kosong. Pertimbangkan --replace default.');
        }

        $this->info("Mengimport: {$filePath}");
        $this->info('SPBKLU: '.($withSpbklu ? 'YA' : 'TIDAK (--spklu-only)'));

        try {
            $summary = $service->importFromFile($filePath, $replace, $withSpbklu);
        } catch (\Throwable $e) {
            $this->error('Import gagal: '.$e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('✓ Import ESDM Singgat berhasil!');
        $this->line("Batch: {$summary['import_batch']}");
        $this->newLine();

        $this->info('── SPKLU (pengisian mobil) ──');
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Total record JSON', $summary['spklu']['total']],
                ['Stasiun diimport', $summary['spklu']['stations']],
                ['Instalasi (mesin)', $summary['spklu']['installations']],
                ['Konektor (plug)', $summary['spklu']['connectors']],
                ['Stasiun lama dihapus', $summary['spklu']['deleted_stations']],
            ]
        );

        if ($withSpbklu) {
            $this->newLine();
            $this->info('── SPBKLU (penukaran baterai motor) ──');
            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    ['Total record JSON', $summary['spbklu']['total']],
                    ['Stasiun diimport', $summary['spbklu']['stations']],
                    ['Kabinet', $summary['spbklu']['cabinets']],
                    ['Baterai', $summary['spbklu']['batteries']],
                    ['Stasiun lama dihapus', $summary['spbklu']['deleted_stations']],
                ]
            );
        }

        return 0;
    }

    /**
     * Download JSON fresh dari API ESDM ke $filePath.
     *
     * @return string|null Path file hasil download, atau null jika gagal.
     */
    private function fetchFresh(string $filePath): ?string
    {
        $this->info('Fetching dari API ESDM: '.self::API_URL.' ...');
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'Origin' => 'https://gatrik.esdm.go.id',
                'Referer' => 'https://gatrik.esdm.go.id/singgat/',
            ])->timeout(120)->post(self::API_URL, []);

            if (! $response->successful()) {
                $this->error("HTTP request gagal: {$response->status()} {$response->reason()}");

                return null;
            }

            $dir = dirname($filePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($filePath, $response->body());
            $sizeMb = round(filesize($filePath) / 1024 / 1024, 1);
            $this->info("✓ Disimpan: {$filePath} ({$sizeMb} MB)");

            return $filePath;
        } catch (\Throwable $e) {
            $this->error('Fetch gagal: '.$e->getMessage());

            return null;
        }
    }
}
