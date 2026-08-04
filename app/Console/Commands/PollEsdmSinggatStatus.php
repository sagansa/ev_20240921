<?php

namespace App\Console\Commands;

use App\Services\EsdmSinggatStatusPollerService;
use Illuminate\Console\Command;

class PollEsdmSinggatStatus extends Command
{
    protected $signature = 'esdm:poll-status
                            {--dry-run : Fetch & diff tanpa tulis ke DB (laporan saja)}';

    protected $description = 'Poll status real-time konektor ESDM Singgat (tiap 10 menit via scheduler). Fetch ESDM, diff status per konektor, catat hanya perubahan, agregasi per stasiun.';

    public function handle(EsdmSinggatStatusPollerService $service): int
    {
        // JSON ESDM 72MB → ~170MB di memori saat decode. Naikkan limit.
        ini_set('memory_limit', '512M');

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODE DRY-RUN — tidak ada perubahan ke DB.');
        }

        $this->info('Polling status ESDM Singgat...');

        try {
            $summary = $service->poll(function ($phase, $n) {
                if ($phase === 'fetch') {
                    $this->line("  ✓ Fetched {$n} stasiun dari ESDM");
                } elseif ($phase === 'process') {
                    $this->line("  … Diproses {$n} stasiun...");
                }
            });
        } catch (\Throwable $e) {
            $this->error('Poll gagal: '.$e->getMessage());

            return 1;
        }

        $this->newLine();
        $this->info('✓ Poll selesai.');
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['Batch', $summary['batch']],
                ['Fetched at', $summary['fetched_at']],
                ['Fetch duration', $summary['fetch_duration_s'].' s'],
                ['Stasiun diproses', $summary['stations_processed']],
                ['Konektor terlihat', $summary['connectors_seen']],
                ['Status berubah', $summary['status_changed']],
                ['Konektor baru', $summary['new_connectors']],
                ['Log transisi dicatat', $summary['logs_inserted']],
                ['Stasiun diagregasi', $summary['stations_aggregated']],
                ['Status PLN di-fold (match)', $summary['pln_matches_folded'] ?? 0],
            ]
        );

        return 0;
    }
}
