<?php

namespace App\Services;

use App\Models\TypeVehicle;

/**
 * Inti analisis "apa yang baru di laporan ini?" — dipakai command
 * vehicle-sales:preview DAN halaman admin Preview Impor Penjualan.
 * Read-only terhadap katalog & stats (matcher mode preview).
 */
class VehicleSalesPreviewService
{
    public function __construct(
        protected VehicleNameSplitter $splitter,
        protected VehicleSalesMatcher $matcher,
        protected VehicleSalesCsvReader $reader,
    ) {
    }

    /**
     * @return array{summary: array{rows: int, skipped: int, nonBev: int, matched: int, new: int},
     *               new: list<array{brand: string, model: string, type: string, powertrain: string, units: int, brand_name: ?string}>}
     */
    public function analyze(string $csvPath, ?int $month = null): array
    {
        try {
            [$rows, $junkSkipped, $junkRows] = $this->reader->read($csvPath, $month);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $new = [];
        $matched = 0;
        $skipped = $junkSkipped;
        /** @var list<array{brand: string, type_model: string, reason: string}> $skippedRows */
        $skippedRows = $junkRows;

        foreach ($rows as $row) {
            // LAPISAN 0: CONNECTING (master raw_gabungan) — cocok persis
            // tanpa pemecah nama. Ini jalur utama.
            if ($this->matcher->connectingHit($row['brand'], $row['type_model']) !== null) {
                $matched++;

                continue;
            }

            $split = $this->splitter->split($row['brand'], $row['type_model'], $row['fuel']);

            if ($split['flag'] === 'junk' || $split['model'] === '') {
                // Fallback legacy tetap boleh menyelamatkan baris ini via
                // mapping/fuzzy — cek dulu sebelum menjatuhkan junk.
                $probe = $this->matcher->preview($row['brand'], $row['type_model'], $row['type_model']);

                if (! $probe['brand_new'] && ! $probe['model_new']) {
                    $matched++;

                    continue;
                }

                $skipped++;
                $skippedRows[] = [
                    'brand' => $row['brand'],
                    'type_model' => $row['type_model'],
                    'reason' => $split['flag'] === 'junk'
                        ? 'Belum ada di CONNECTING & tak terbaca pemecah nama'
                        : 'Belum ada di CONNECTING & nama model tak terbaca',
                ];

                continue;
            }

            // Semua powertrain dicek terhadap katalog (BEV/HEV/PHEV/ICE).
            $preview = $this->matcher->preview($row['brand'], $split['model'], $row['type_model']);

            if (! $preview['brand_new'] && ! $preview['model_new']) {
                $matched++;

                continue;
            }

            $key = strtoupper($row['brand']).'|'.strtoupper($split['model']);
            $units = $month !== null
                ? ($row['units'] ?? $row['cells'][$month] ?? 0)
                : array_sum($row['cells']);

            // Nama BARU ditandai mentah apa adanya (brand + type model
            // utuh) — tanpa pemecah nama, siap digabung ke CONNECTING.
            if (! isset($new[$key])) {
                $new[$key] = [
                    'brand' => $row['brand'],
                    'model' => $row['type_model'],
                    'type' => '',
                    'powertrain' => $split['powertrain'],
                    'units' => 0,
                    'brand_name' => $preview['brand_name'],
                ];
            }

            $new[$key]['units'] += $units;
        }

        return [
            'summary' => [
                'rows' => count($rows),
                'skipped' => $skipped,
                'matched' => $matched,
                'new' => count($new),
            ],
            'new' => array_values($new),
            'skipped_rows' => $skippedRows,
        ];
    }

    /** Tulis kombinasi baru sebagai CSV siap-gabung ke CONNECTING. */
    public function exportNewCsv(array $newRows, string $outPath): int
    {
        $handle = fopen($outPath, 'w');
        fputcsv($handle, ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE']);

        foreach ($newRows as $n) {
            fputcsv($handle, [$n['brand'], $n['model'], $n['type'], $n['powertrain'], '', '']);
        }

        fclose($handle);

        return count($newRows);
    }

    /** True bila type sudah terdaftar di bawah model (read-only). */
    public function typeExists(int $modelId, string $typeName): bool
    {
        return TypeVehicle::query()
            ->where('model_vehicle_id', $modelId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($typeName))])
            ->exists();
    }
}
