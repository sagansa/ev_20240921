<?php

namespace App\Console\Commands;

use App\Services\CanonicalStationHydrateService;
use Illuminate\Console\Command;

class HydratePlnCanonicalStations extends Command
{
    protected $signature = 'pln:hydrate-canonical';

    protected $description = 'Hydrate tabel kanonik charging_stations dari master PLN (pln_charger_locations). Idempoten — aman dijalankan berulang.';

    public function handle(CanonicalStationHydrateService $service): int
    {
        $this->info('Hydrate charging_stations dari PLN...');

        try {
            $stats = $service->hydrateFromPln();
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
