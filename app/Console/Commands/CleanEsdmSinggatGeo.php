<?php

namespace App\Console\Commands;

use App\Services\EsdmSinggatGeoCleaningService;
use Illuminate\Console\Command;

class CleanEsdmSinggatGeo extends Command
{
    protected $signature = 'esdm:clean-geo
                            {--dry-run : Hitung & tampilkan laporan tanpa menulis ke DB}';

    protected $description = 'Normalisasi koordinat lat/lgn ESDM Singgat (mengisi kolom latitude/longitude/geo_status dari *_raw). Idempoten — aman dijalankan berulang.';

    public function handle(EsdmSinggatGeoCleaningService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODE DRY-RUN — tidak ada perubahan ke DB.');
        } else {
            $this->info('Mengisi latitude/longitude/geo_status dari *_raw...');
        }

        $this->newLine();
        $summary = $service->clean($dryRun);

        // SPKLU
        $spklu = $summary['spklu'];
        $this->info('── SPKLU (pengisian mobil) ──');
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Processed', $spklu['processed']],
                ['OK (valid apa adanya)', $spklu['ok']],
                ['Fixed (diperbaiki)', $spklu['fixed']],
                ['Unresolved', $spklu['unresolved']],
                ['Null', $spklu['null'] ?? 0],
            ]
        );

        if (! empty($spklu['unresolved_details'])) {
            $this->newLine();
            $this->warn('Record SPKLU unresolved (perlu koreksi manual):');
            $rows = array_map(fn ($r) => [
                $r['id'],
                $r['lat_raw'] ?? 'null',
                $r['lng_raw'] ?? 'null',
                substr($r['notes'] ?? '', 0, 60),
            ], $spklu['unresolved_details']);
            $this->table(['ID', 'lat_raw', 'lng_raw', 'notes'], $rows);
        }

        // SPBKLU
        $this->newLine();
        $spbklu = $summary['spbklu'];
        $this->info('── SPBKLU (penukaran baterai motor) ──');
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Processed', $spbklu['processed']],
                ['OK (valid apa adanya)', $spbklu['ok']],
                ['Fixed (diperbaiki)', $spbklu['fixed']],
                ['Unresolved', $spbklu['unresolved']],
                ['Null', $spbklu['null'] ?? 0],
            ]
        );

        if (! empty($spbklu['unresolved_details'])) {
            $this->newLine();
            $this->warn('Record SPBKLU unresolved:');
            $rows = array_map(fn ($r) => [
                $r['id'],
                $r['lat_raw'] ?? 'null',
                $r['lng_raw'] ?? 'null',
                substr($r['notes'] ?? '', 0, 60),
            ], $spbklu['unresolved_details']);
            $this->table(['ID', 'lat_raw', 'lng_raw', 'notes'], $rows);
        }

        $this->newLine();
        if ($dryRun) {
            $this->comment('Dry-run selesai. Jalankan tanpa --dry-run untuk menerapkan.');
        } else {
            $this->info('✓ Cleaning selesai. Kolom *_raw tetap utuh untuk audit.');
        }

        return 0;
    }
}
