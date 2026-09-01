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
            'typeBaru' => [], 'klasifikasiBeda' => [],
            'dbBrandTanpaCsv' => [], 'dbModelTanpaCsv' => [],
        ];
        $csvBrandRefs = [];
        $csvModelRefs = [];
        $seen = [];

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

            $dbType = $typesByKey[$dbModel->id.'|'.$norm($type)] ?? null;
            if ($type !== '' && $dbType === null) {
                $report['typeBaru'][] = ['brand' => $brand, 'model' => $model, 'type' => $type];
            }

            $diffs = [];
            if ($pt !== '' && $dbModel->powertrain !== $pt) $diffs[] = "powertrain DB={$dbModel->powertrain} CSV={$pt}";
            if ($category !== '' && $dbModel->category !== $category) $diffs[] = "category DB={$dbModel->category} CSV={$category}";
            if ($size !== '' && $dbModel->size_class !== $size) $diffs[] = "size DB={$dbModel->size_class} CSV={$size}";

            if ($diffs !== []) {
                $dKey = $mKey.'|'.implode(';', $diffs);
                $report['klasifikasiBeda'][$dKey] ??= [
                    'brand' => $brand, 'model' => $model, 'diff' => implode('; ', $diffs),
                ];
            } elseif ($dbType !== null || $type === '') {
                $report['match'][] = ['brand' => $brand, 'model' => $model, 'type' => $type];
            }
        }
        fclose($handle);

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
