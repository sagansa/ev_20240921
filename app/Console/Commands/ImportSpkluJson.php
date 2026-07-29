<?php

namespace App\Console\Commands;

use App\Services\SpkluJsonImportService;
use Illuminate\Console\Command;

class ImportSpkluJson extends Command
{
    protected $signature = 'spklu:import-json
                            {file? : Path to JSON file (default: ../data/data_spklu.json)}
                            {--append : Do not delete existing data; update/merge instead}';

    protected $description = 'Import SPKLU data from data_spklu.json into spklu_locations and spklu_charger_boxes tables (replaces existing data by default)';

    public function handle(SpkluJsonImportService $importService): int
    {
        $filePath = $this->argument('file')
            ?? base_path('../data/data_spklu.json');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        $replaceExisting = ! $this->option('append');

        if ($replaceExisting) {
            $this->warn("MODE REPLACE — Data lama pada spklu_locations dan spklu_charger_boxes akan dihapus terlebih dahulu.");
        } else {
            $this->info("MODE MERGE/APPEND — Data lama akan di-update / digabung.");
        }

        $this->info("Membaca dan mengimport file JSON: {$filePath}...");

        try {
            $summary = $importService->importFromFile($filePath, $replaceExisting);

            $this->newLine();
            $this->info("Import SPKLU JSON berhasil!");
            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    ['Total Record JSON', $summary['total_records']],
                    ['Lokasi Diimport', $summary['inserted_locations']],
                    ['Charger Box Diimport', $summary['inserted_charger_boxes']],
                    ['Lokasi Lama Dihapus', $summary['deleted_locations']],
                    ['Charger Box Lama Dihapus', $summary['deleted_charger_boxes']],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Import gagal: " . $e->getMessage());
            return 1;
        }
    }
}
