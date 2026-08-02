<?php

namespace App\Console\Commands;

use App\Services\CanonicalStationHydrateService;
use Illuminate\Console\Command;

class HydrateCanonicalStations extends Command
{
    protected $signature = 'esdm:hydrate-canonical';

    protected $description = 'Hydrate tabel kanonik charging_stations dari data ESDM Singgat (roll-up instalasi, guess provider, snapshot status). Idempoten — aman dijalankan berulang.';

    public function handle(CanonicalStationHydrateService $service): int
    {
        $this->info('Hydrate charging_stations dari ESDM Singgat...');

        try {
            $stats = $service->hydrateFromEsdm();
        } catch (\Throwable $e) {
            $this->error('Hydrate gagal: '.$e->getMessage());

            return 1;
        }

        $this->newLine();
        $this->info('✓ Hydrate selesai.');
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Stasiun diproses', $stats['processed']],
                ['Dibuat', $stats['created']],
                ['Diupdate', $stats['updated']],
                ['Dilewati (tidak layak)', $stats['skipped']],
                ['Child charger ditulis', $stats['chargers']],
            ]
        );

        return 0;
    }
}
