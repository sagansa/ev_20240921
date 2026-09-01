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
            [$rows, $junkSkipped] = $this->reader->read($csvPath, $month);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $new = [];
        $matched = 0;
        $skipped = $junkSkipped;

        foreach ($rows as $row) {
            $split = $this->splitter->split($row['brand'], $row['type_model'], $row['fuel']);

            if ($split['flag'] === 'junk' || $split['model'] === '') {
                $skipped++;

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

            if (! isset($new[$key])) {
                $new[$key] = [
                    'brand' => $row['brand'],
                    'model' => $split['model'],
                    'type' => $split['type'],
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
