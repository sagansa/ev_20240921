<?php

namespace App\Services;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use Illuminate\Support\Facades\DB;

/**
 * Membandingkan isi file CONNECTING vs katalog di DB — read-only.
 * Dipakai command vehicle-connecting:verify DAN halaman admin
 * Sinkronisasi CONNECTING.
 */
class VehicleConnectingComparer
{
    /** @var array<string, string> header wajib file CONNECTING */
    public const REQUIRED_COLUMNS = ['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE'];

    /**
     * Nilai seragam dari daftar (hanya yang terisi): sama semua → nilainya;
     * ada yang beda/kosong → null (tidak bisa diputuskan per keluarga).
     *
     * @param list<string> $values
     */
    protected function uniformValue(array $values): ?string
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== '' && $v !== null));

        if ($values === []) {
            return null;
        }

        $first = $values[0];
        foreach ($values as $v) {
            if ($v !== $first) {
                return null;
            }
        }

        return $first;
    }

    /**
     * Bandingkan CSV vs katalog.
     *
     * @return array{match: int, brandBaru: list<array>, modelBaru: list<array>,
     *               typeBaru: list<array>, klasifikasiBeda: list<array>,
     *               dbBrandTanpaCsv: list<array>, dbModelTanpaCsv: list<array>}
     *
     * @throws \RuntimeException bila file/header tidak valid
     */
    public function compare(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("File tidak bisa dibuka: {$csvPath}");
        }

        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        foreach (self::REQUIRED_COLUMNS as $need) {
            if (! in_array($need, $hdr, true)) {
                fclose($handle);
                throw new \RuntimeException("Header wajib memuat kolom: {$need}");
            }
        }
        $idx = fn (string $n): int => (int) array_search($n, $hdr, true);

        $norm = fn (?string $v): string => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)) ?? '');
        $matcher = app(VehicleSalesMatcher::class);
        $canonical = fn (string $raw): string => $norm($matcher->canonicalBrandName($raw));

        // Katalog DB (sekali muat).
        $brandsById = BrandVehicle::all()->keyBy('id');
        $brandByKey = [];
        foreach ($brandsById as $b) {
            $brandByKey[$norm($b->name)] ??= $b->id;
        }

        $modelsByKey = [];
        foreach (ModelVehicle::with('brandVehicle')->get() as $m) {
            $modelsByKey[$m->brand_vehicle_id.'|'.$norm($m->name)] ??= $m;
        }

        $typesByKey = [];
        foreach (TypeVehicle::all() as $t) {
            $typesByKey[$t->model_vehicle_id.'|'.$norm($t->name)] ??= $t;
        }

        $report = [
            'match' => [], 'brandBaru' => [], 'modelBaru' => [],
            'typeBaru' => [], 'klasifikasiBeda' => [], 'csvTidakKonsisten' => [],
            'dbBrandTanpaCsv' => [], 'dbModelTanpaCsv' => [],
        ];
        $csvBrandRefs = [];
        $csvModelRefs = [];
        $seen = [];

        // Pass 1: kumpulkan baris per keluarga (brand+model) + entitas baru.
        $families = [];
        while (($r = fgetcsv($handle)) !== false) {
            if (count($r) < 6 && trim(implode('', $r)) === '') continue;
            $brand = trim((string) $r[$idx('BRAND')]);
            $model = trim((string) $r[$idx('MODEL')]);
            $type = trim((string) $r[$idx('TYPE')]);
            $pt = strtoupper(trim((string) $r[$idx('POWERTRAIN')]));
            $category = trim((string) $r[$idx('CATEGORY')]);
            $size = trim((string) $r[$idx('SIZE')]);

            if ($brand === '' || $model === '') continue;

            $bKey = $canonical($brand);
            $mKey = $bKey.'|'.$norm($model);
            $rowKey = $mKey.'|'.$norm($type).'|'.$pt.'|'.$category.'|'.$size;
            if (isset($seen[$rowKey])) continue;
            $seen[$rowKey] = true;

            $csvBrandRefs[$bKey] = $brand;
            $brandId = $brandByKey[$bKey] ?? null;
            if ($brandId !== null) {
                $csvModelRefs[$brandId.'|'.$norm($model)] = [$brand, $model];
            }

            $dbModel = ($brandId !== null) ? ($modelsByKey[$brandId.'|'.$norm($model)] ?? null) : null;

            if ($brandId === null) {
                $report['brandBaru'][] = compact('brand', 'model', 'type', 'pt', 'category', 'size');
                continue;
            }
            if ($dbModel === null) {
                $report['modelBaru'][] = compact('brand', 'model', 'type', 'pt', 'category', 'size');
                continue;
            }

            $families[$mKey]['brand'] = $brand;
            $families[$mKey]['model'] = $model;
            $families[$mKey]['db'] = $dbModel;
            $families[$mKey]['rows'][] = ['type' => $type, 'pt' => $pt, 'category' => $category, 'size' => $size];
        }
        fclose($handle);

        // Pass 2: evaluasi per keluarga — nilai CSV yang SERAGAM dibandingkan
        // dgn DB; keluarga campuran (mis. varian G dan Hev) dilaporkan sbg
        // informasi, bukan aksi, supaya laporan stabil setelah sinkronisasi.
        foreach ($families as $family) {
            $db = $family['db'];
            $csvPt = $this->uniformValue(array_column($family['rows'], 'pt'));
            $csvCategory = $this->uniformValue(array_column($family['rows'], 'category'));
            $csvSize = $this->uniformValue(array_column($family['rows'], 'size'));

            $diffs = [];
            if ($csvPt !== null && $db->powertrain !== $csvPt) $diffs[] = "powertrain DB={$db->powertrain} CSV={$csvPt}";
            if ($csvCategory !== null && $db->category !== $csvCategory) $diffs[] = "category DB={$db->category} CSV={$csvCategory}";
            if ($csvSize !== null && $db->size_class !== $csvSize) $diffs[] = "size DB={$db->size_class} CSV={$csvSize}";

            if ($diffs !== []) {
                $report['klasifikasiBeda'][] = [
                    'brand' => $family['brand'], 'model' => $family['model'], 'diff' => implode('; ', $diffs),
                ];
            } elseif ($csvPt === null || $csvCategory === null || $csvSize === null) {
                $report['csvTidakKonsisten'][] = [
                    'brand' => $family['brand'], 'model' => $family['model'],
                    'detail' => 'varian CSV tidak seragam (perlu dirapikan di CSV)',
                ];
            } else {
                $report['match'][] = ['brand' => $family['brand'], 'model' => $family['model']];
            }

            foreach ($family['rows'] as $row) {
                if ($row['type'] === '') continue;
                $dbType = $typesByKey[$db->id.'|'.$norm($row['type'])] ?? null;
                if ($dbType === null) {
                    $report['typeBaru'][] = [
                        'brand' => $family['brand'], 'model' => $family['model'], 'type' => $row['type'],
                    ];
                }
            }
        }

        foreach ($brandByKey as $bKey => $bId) {
            if (! isset($csvBrandRefs[$bKey])) {
                $report['dbBrandTanpaCsv'][] = ['brand' => $brandsById[$bId]->name];
            }
        }
        foreach ($modelsByKey as $mKey => $m) {
            if (! isset($csvModelRefs[$mKey])) {
                $report['dbModelTanpaCsv'][] = [
                    'brand' => $m->brandVehicle?->name ?? '?',
                    'model' => $m->name,
                    'category' => $m->category,
                ];
            }
        }

        return $report;
    }
}
