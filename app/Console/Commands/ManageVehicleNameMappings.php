<?php

namespace App\Console\Commands;

use App\Models\VehicleNameMapping;
use App\Models\VehicleSalesStat;
use App\Services\VehicleSalesMatcher;
use Illuminate\Console\Command;

/**
 * Kelola tabel mapping eksplisit (lapisan pertama matcher):
 *
 *  - import : isi dari CSV (RAW_BRAND, RAW_MODEL, BRAND_VEHICLE,
 *             MODEL_VEHICLE, TYPE_VEHICLE opsional, CATATAN opsional).
 *             Katalog harus sudah ada — baris yg merujuk katalog tidak ada
 *             dilaporkan, tidak dibuat otomatis.
 *  - relink : perbarui stats lama yang masih NULL-link agar memakai mapping
 *             (tanpa re-import file; hanya baris raw yang cocok mapping).
 *
 * Contoh:
 *   php artisan vehicle-mapping:import-csv docs/csv/vehicle-name-mappings.csv
 *   php artisan vehicle-mapping:relink --year=2025
 */
class ManageVehicleNameMappings extends Command
{
    protected $signature = 'vehicle-mapping
        {action : import|relink}
        {--csv= : Path CSV (utk action=import)}
        {--year= : Batasi tahun stats (utk action=relink)}';

    protected $description = 'Import CSV mapping nama laporan → katalog, atau relink stats yang masih NULL-link';

    public function handle(VehicleSalesMatcher $matcher): int
    {
        return match ($this->argument('action')) {
            'import' => $this->import($matcher),
            'relink' => $this->relink($matcher),
            default => $this->error('action harus import atau relink.') ?: self::FAILURE,
        };
    }

    protected function import(VehicleSalesMatcher $matcher): int
    {
        $csv = (string) $this->option('csv');

        if (! is_file($csv)) {
            $this->error("File tidak ada: {$csv}");

            return self::FAILURE;
        }

        $handle = fopen($csv, 'r');
        $header = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        $idx = fn (string $name): ?int => ($i = array_search($name, $header, true)) === false ? null : $i;

        $need = ['RAW_BRAND', 'RAW_MODEL', 'BRAND_VEHICLE', 'MODEL_VEHICLE'];

        if (in_array(null, array_map($idx, $need), true)) {
            $this->error('Header wajib memuat: '.implode(', ', $need));
            fclose($handle);

            return self::FAILURE;
        }

        $saved = 0;
        $failed = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rawBrand = trim((string) $row[$idx('RAW_BRAND')]);
            $rawModel = trim((string) $row[$idx('RAW_MODEL')]);

            if ($rawBrand === '' || $rawModel === '') {
                continue;
            }

            $typeName = $idx('TYPE_VEHICLE') !== null ? trim((string) $row[$idx('TYPE_VEHICLE')]) : null;
            $catatan = $idx('CATATAN') !== null ? trim((string) $row[$idx('CATATAN')]) : null;

            $mapping = VehicleNameMapping::record(
                $rawBrand,
                $rawModel,
                trim((string) $row[$idx('BRAND_VEHICLE')]),
                trim((string) $row[$idx('MODEL_VEHICLE')]),
                $typeName !== '' ? $typeName : null,
                $catatan !== '' ? $catatan : null,
            );

            if ($mapping === null) {
                $failed[] = "$rawBrand | $rawModel (katalog tidak ada?)";

                continue;
            }

            $saved++;
        }

        fclose($handle);

        $this->info("Mapping tersimpan: {$saved}");

        if ($failed !== []) {
            $this->warn('Gagal (katalog belum ada): '.count($failed));
            $this->line('  '.implode('; ', array_slice($failed, 0, 8)));
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    protected function relink(VehicleSalesMatcher $matcher): int
    {
        // Ambil semua mapping, terapkan ke stats NULL-link dgn kunci ternormalisasi.
        $mappings = VehicleNameMapping::all();
        $updated = 0;

        foreach ($mappings as $mapping) {
            $query = VehicleSalesStat::query()
                ->whereNull('model_vehicle_id')
                ->whereRaw('UPPER(TRIM(raw_brand)) = ?', [mb_strtoupper($mapping->raw_brand_norm)])
                ->where(function ($q) use ($mapping) {
                    $q->whereRaw('UPPER(TRIM(raw_model)) = ?', [mb_strtoupper($mapping->raw_model_norm)])
                        ->orWhereRaw('UPPER(REPLACE(TRIM(raw_model), " ", "")) = ?', [mb_strtoupper(preg_replace('/\s+/', '', $mapping->raw_model_norm))]);
                });

            if ($year = $this->option('year')) {
                $query->where('year', (int) $year);
            }

            $updated += $query->update([
                'brand_vehicle_id' => $mapping->brand_vehicle_id,
                'model_vehicle_id' => $mapping->model_vehicle_id,
                'type_vehicle_id' => $mapping->type_vehicle_id,
            ]);
        }

        $this->info("Baris stats ter-relink: {$updated}");

        return self::SUCCESS;
    }
}
