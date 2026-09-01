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
     * Nilai keluarga dari daftar sel CSV: hanya nilai non-kosong yang
     * dihitung. Kosong = "tidak diset" (sah, mis. size utk City Car).
     *
     * @return array{0: ?string, 1: ?string} [nilai kanonis, deskripsi konflik]
     */
    protected function familyValue(array $values, string $field): array
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== '' && $v !== null));

        if ($values === []) {
            return [null, null];
        }

        $unique = array_values(array_unique($values));

        if (count($unique) === 1) {
            return [$unique[0], null];
        }

        // Beberapa nilai berbeda — tidak bisa diputuskan otomatis.
        return [null, "CSV {$field} tidak seragam: ".implode(' vs ', $unique)];
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
        // dgn DB. Konflik (dua nilai berbeda yang keduanya terisi) dilaporkan
        // sbg informasi; nilai kosong = "tidak diset di CSV" → bukan konflik.
        foreach ($families as $family) {
            $db = $family['db'];

            [$csvPt, $ptConflict] = $this->familyValue(array_column($family['rows'], 'pt'), 'powertrain');
            [$csvCategory, $catConflict] = $this->familyValue(array_column($family['rows'], 'category'), 'category');
            [$csvSize, $sizeConflict] = $this->familyValue(array_column($family['rows'], 'size_class'), 'size');

            $diffs = [];
            if ($csvPt !== null && $db->powertrain !== $csvPt) $diffs[] = "powertrain DB={$db->powertrain} CSV={$csvPt}";
            if ($csvCategory !== null && $db->category !== $csvCategory) $diffs[] = "category DB={$db->category} CSV={$csvCategory}";
            if ($csvSize !== null && $db->size_class !== $csvSize) $diffs[] = "size DB={$db->size_class} CSV={$csvSize}";

            if ($diffs !== []) {
                $report['klasifikasiBeda'][] = [
                    'brand' => $family['brand'], 'model' => $family['model'], 'diff' => implode('; ', $diffs),
                ];
            }

            $conflicts = array_filter([$ptConflict, $catConflict, $sizeConflict]);
            if ($conflicts !== []) {
                $report['csvTidakKonsisten'][] = [
                    'brand' => $family['brand'], 'model' => $family['model'],
                    'detail' => implode('; ', $conflicts),
                ];
            } elseif ($diffs === []) {
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
