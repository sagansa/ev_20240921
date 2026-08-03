<?php

namespace App\Console\Commands;

use App\Services\GeoVerificationService;
use Illuminate\Console\Command;

class VerifyEsdmGeo extends Command
{
    protected $signature = 'esdm:verify-geo
                            {--step=all : Tahapan verifikasi: bbox | osm | all}
                            {--dry-run : Hitung & tampilkan laporan tanpa menulis ke DB}';

    protected $description = 'Verifikasi geolokasi stasiun ESDM SPKLU: cek bbox provinsi + forward-search OSM Nominatim. Idempoten.';

    public function handle(GeoVerificationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $step = (string) $this->option('step');
        $bboxOnly = $step === 'bbox';
        $osmOnly = $step === 'osm';

        if (! in_array($step, ['all', 'bbox', 'osm'], true)) {
            $this->error("--step harus salah satu dari: all, bbox, osm (diberikan: {$step}).");

            return 1;
        }

        if ($dryRun) {
            $this->warn('MODE DRY-RUN — tidak ada perubahan ke DB.');
        } else {
            $this->info('Memverifikasi geolokasi stasiun ESDM SPKLU...');
        }

        $this->newLine();

        $progress = function (string $message, int $done, int $total): void {
            if ($done % 100 === 0 || $done === $total) {
                $this->output->write("\r{$message}");
            }
        };

        $summary = $service->verifyAll(
            dryRun: $dryRun,
            bboxOnly: $bboxOnly,
            osmOnly: $osmOnly,
            progress: $progress
        );

        $this->newLine(2);
        $this->info('── RINGKASAN VERIFIKASI ──');
        $this->table(
            ['Level', 'Jumlah'],
            [
                ['Province mismatch', $summary['province_mismatch']],
                ['Verified (<200m)', $summary['verified']],
                ['Drift minor (200m–2km)', $summary['drift_minor']],
                ['Drift major (>2km)', $summary['drift_major']],
                ['Not found (perlu review manual)', $summary['not_found']],
                ['Skipped (tanpa koordinat)', $summary['skipped']],
                ['── Total diproses ──', $summary['processed']],
            ]
        );

        $this->newLine();
        if ($dryRun) {
            $this->comment('Dry-run selesai. Jalankan tanpa --dry-run untuk menerapkan.');
        } else {
            $this->info('✓ Verifikasi selesai. Item drift/not_found/province_mismatch siap direview manual di Filament.');
        }

        return 0;
    }
}
