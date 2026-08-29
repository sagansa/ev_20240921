<?php

namespace App\Console\Commands;

use App\Services\VehicleNameSplitter;
use Illuminate\Console\Command;

/**
 * Menghasilkan CSV "CONNECTING" hasil derivasi otomatis: kombinasi unik
 * BRAND + TYPE MODEL dari satu atau lebih CSV tahunan GAIKINDO dipetakan ke
 * MODEL (keluarga), TYPE (varian penuh), POWERTRAIN — untuk direview manusia
 * sebelum diimpor.
 *
 * Contoh: php artisan vehicle-name:preview docs/csv/GAIKINDO_20*.csv
 */
class PreviewVehicleNames extends Command
{
    protected $signature = 'vehicle-name:preview
        {csv* : File CSV tahunan (kolom BRAND, TYPE MODEL, FUEL opsional)}
        {--out= : Path output (default: storage/app/vehicle-name-preview.csv)}';

    protected $description = 'Derivasi BRAND/MODEL/TYPE/POWERTRAIN dari CSV GAIKINDO untuk direview';

    public function handle(VehicleNameSplitter $splitter): int
    {
        $combos = [];

        foreach ($this->argument('csv') as $path) {
            if (! is_file($path)) {
                $this->warn("Lewati (tidak ada): {$path}");

                continue;
            }

            $added = $this->collect($path, $combos);
            $this->info("{$path}: {$added} kombinasi baru");
        }

        if ($combos === []) {
            $this->error('Tidak ada kombinasi terbaca.');

            return self::FAILURE;
        }

        $rows = [];
        $junk = 0;

        foreach ($combos as $combo) {
            $result = $splitter->split($combo['brand'], $combo['type_model'], $combo['fuel']);

            if ($result['flag'] === 'junk') {
                $junk++;

                continue;
            }

            $key = $result['brand'].'|'.$result['model'].'|'.$result['type'].'|'.$result['powertrain'];
            $rows[$key] ??= [
                'BRAND' => $result['brand'],
                'MODEL' => $result['model'],
                'TYPE' => $result['type'],
                'POWERTRAIN' => $result['powertrain'],
                'CONFIDENCE' => $result['confidence'],
                'FAMILY_SOURCE' => $result['family_source'],
                'FLAG' => $result['flag'] ?? '',
                'COMBOS' => 0,
            ];
            $rows[$key]['COMBOS']++;
        }

        usort($rows, fn ($a, $b) => [$a['BRAND'], $a['MODEL'], $a['TYPE']] <=> [$b['BRAND'], $b['MODEL'], $b['TYPE']]);

        $out = $this->option('out') ?: storage_path('app/vehicle-name-preview.csv');
        $this->writeCsv($out, $rows);

        $low = count(array_filter($rows, fn ($r) => $r['CONFIDENCE'] === 'low'));

        $this->info(sprintf(
            '%d baris mapping unik → %s (low-confidence: %d, junk dilewati: %d)',
            count($rows), $out, $low, $junk,
        ));

        return self::SUCCESS;
    }

    /** @param array<string, array{brand:string,type_model:string,fuel:?string,count:int}> $combos */
    protected function collect(string $path, array &$combos): int
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        $header = array_map(fn ($h) => strtoupper(trim((string) $h)), fgetcsv($handle) ?: []);
        $idx = fn (string $name): ?int => ($i = array_search($name, $header, true)) === false ? null : $i;

        $brandI = $idx('BRAND');
        $tmI = $idx('TYPE MODEL') ?? $idx('MODEL TYPE');
        $fuelI = $idx('FUEL');

        if ($brandI === null || $tmI === null) {
            fclose($handle);
            $this->warn("{$path}: header BRAND / TYPE MODEL tidak ditemukan");

            return 0;
        }

        $before = count($combos);

        while (($row = fgetcsv($handle)) !== false) {
            $brand = trim((string) ($row[$brandI] ?? ''));
            $typeModel = trim((string) ($row[$tmI] ?? ''));
            $fuelRaw = $fuelI === null ? null : trim((string) ($row[$fuelI] ?? ''));
            $fuel = $fuelRaw === '' ? null : $fuelRaw;

            if ($brand === '' && $typeModel === '') {
                continue;
            }

            $key = mb_strtoupper($brand).'|'.mb_strtoupper($typeModel).'|'.mb_strtoupper((string) $fuel);
            $combos[$key] ??= ['brand' => $brand, 'type_model' => $typeModel, 'fuel' => $fuel, 'count' => 0];
            $combos[$key]['count']++;
        }

        fclose($handle);

        return count($combos) - $before;
    }

    protected function writeCsv(string $path, array $rows): void
    {
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, array_keys($rows[0] ?? ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CONFIDENCE', 'FAMILY_SOURCE', 'FLAG', 'COMBOS']));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }
}
