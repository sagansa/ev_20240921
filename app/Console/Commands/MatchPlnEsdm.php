<?php

namespace App\Console\Commands;

use App\Services\PlnEsdmMatchService;
use Illuminate\Console\Command;

class MatchPlnEsdm extends Command
{
    protected $signature = 'pln:match-esdm
                            {--force : Overwrite keputusan final admin/system}
                            {--dry-run : Hitung tanpa menulis ke DB}';

    protected $description = 'Match stasiun PLN ↔ ESDM (geo+nama deterministik → AI utk kasus ambiguous). Menghasilkan tabel link pln_esdm_station_matches; lalu fold status ESDM ke PLN (non dry-run).';

    public function handle(PlnEsdmMatchService $service): int
    {
        ini_set('memory_limit', '512M');

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODE DRY-RUN — tidak ada perubahan ke DB.');
        }
        if ($force) {
            $this->warn('MODE FORCE — keputusan final admin/system akan di-overwrite.');
        }

        $this->info('Matching PLN ↔ ESDM dimulai...');

        try {
            $summary = $service->match(
                force: $force,
                dryRun: $dryRun,
                progress: function (string $message, int $done, int $total) {
                    if ($done === 1 || $done % 25 === 0 || $done === $total) {
                        $this->line("  … {$message}");
                    }
                },
            );
        } catch (\Throwable $e) {
            $this->error('Matching gagal: '.$e->getMessage());

            return 1;
        }

        $this->newLine();
        $this->info('✓ Matching selesai.');
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['Stasiun PLN dengan kandidat', $summary['processed']],
                ['Auto-link (approved)', $summary['auto_linked']],
                ['AI suggest (perlu approve)', $summary['ai_suggested']],
                ['AI rejected', $summary['ai_rejected']],
                ['Pending review', $summary['pending_review']],
                ['AI errors', $summary['ai_errors']],
                ['Fallback (AI down)', $summary['fallbacks']],
                ['Keputusan final dipertahankan', $summary['skipped_preserved']],
            ]
        );

        if (! $dryRun) {
            $folded = $service->applyStatusToCanonical();
            $this->info("✓ Status canonical di-fold dari ESDM ke PLN ({$folded} stasiun PLN).");
        }

        return 0;
    }
}
