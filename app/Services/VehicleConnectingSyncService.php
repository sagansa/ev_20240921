<?php

namespace App\Services;

use App\Filament\Imports\VehicleHierarchyImporter;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
use App\Models\VehicleSalesStat;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

/**
 * Eksekusi sinkronisasi CONNECTING → DB, dipakai halaman admin
 * "Sinkronisasi CONNECTING". Urutan:
 *  1. importConnectingTable()  → persistensi isi CSV ke vehicle_connectings
 *  2. importCatalog()          → buat/perbarui brand-model-type + klasifikasi
 *  3. backfillCategories()     → pastikan kategori model existing terisi
 *  4. flushMarketCache()       → respons Pasar EV langsung segar
 *
 * Semua langkah idempoten — aman dijalankan ulang.
 */
class VehicleConnectingSyncService
{
    /**
     * Impor katalog dari CSV: buat/perbarui brand-model-type + klasifikasi.
     *
     * @return array{processed: int, brands: int, models: int, types: int, failed: list<string>}
     */
    public function importCatalog(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("File tidak bisa dibuka: {$csvPath}");
        }

        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        foreach (['BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE'] as $need) {
            if (! in_array($need, $hdr, true)) {
                fclose($handle);
                throw new \RuntimeException("Header wajib memuat kolom: {$need}");
            }
        }
        $i = fn (string $n): int => (int) array_search($n, $hdr, true);
        [$iB, $iM, $iT, $iP, $iC, $iS] = [$i('BRAND'), $i('MODEL'), $i('TYPE'), $i('POWERTRAIN'), $i('CATEGORY'), $i('SIZE')];

        $columnMap = ['BRAND' => 'BRAND', 'MODEL' => 'MODEL', 'TYPE' => 'TYPE',
            'POWERTRAIN' => 'POWERTRAIN', 'CATEGORY' => 'CATEGORY', 'SIZE' => 'SIZE'];

        $import = Import::create([
            'file_name' => basename($csvPath),
            'file_path' => $csvPath,
            'importer' => VehicleHierarchyImporter::class,
            'user_id' => auth()->id() ?? \App\Models\User::query()->value('id') ?? 1,
            'total_rows' => 0,
        ]);

        $processed = 0; $failed = []; $seen = [];
        $b0 = BrandVehicle::count(); $m0 = ModelVehicle::count(); $t0 = TypeVehicle::count();

        while (($r = fgetcsv($handle)) !== false) {
            if (count($r) < 6 && trim(implode('', $r)) === '') continue;

            $row = [
                'BRAND' => trim((string) $r[$iB]),
                'MODEL' => trim((string) $r[$iM]),
                'TYPE' => trim((string) $r[$iT]),
                'POWERTRAIN' => trim((string) $r[$iP]),
                'CATEGORY' => trim((string) $r[$iC]),
                'SIZE' => trim((string) $r[$iS]),
            ];

            $key = strtoupper($row['BRAND']).'|'.strtoupper($row['MODEL']).'|'.strtoupper($row['TYPE']);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            try {
                $importer = new VehicleHierarchyImporter($import, $columnMap, []);
                $importer($row);
                $processed++;
            } catch (\Throwable $e) {
                $failed[] = $row['BRAND'].' | '.$row['MODEL'].' | '.$row['TYPE'].': '.substr($e->getMessage(), 0, 100);
            }
        }
        fclose($handle);

        return [
            'processed' => $processed,
            'brands' => BrandVehicle::count() - $b0,
            'models' => ModelVehicle::count() - $m0,
            'types' => TypeVehicle::count() - $t0,
            'failed' => $failed,
        ];
    }

    /**
     * Sinkronkan tabel vehicle_connectings dari CSV (upsert by raw_gabungan).
     *
     * @return array{saved: int, unresolved: list<string>}
     */
    public function importConnectingTable(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("File tidak bisa dibuka: {$csvPath}");
        }

        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        foreach (['BRAND MODEL TYPE', 'BRAND', 'MODEL', 'TYPE', 'POWERTRAIN', 'CATEGORY', 'SIZE'] as $need) {
            if (! in_array($need, $hdr, true)) {
                fclose($handle);
                throw new \RuntimeException("Header wajib memuat kolom: {$need}");
            }
        }
        $i = fn (string $n): int => (int) array_search($n, $hdr, true);
        [$iG, $iF, $iB, $iM, $iT, $iP, $iC, $iS] = [
            $i('BRAND MODEL TYPE'), $i('FUEL') ?? 1, $i('BRAND'), $i('MODEL'),
            $i('TYPE'), $i('POWERTRAIN'), $i('CATEGORY'), $i('SIZE'),
        ];

        $matcher = app(VehicleSalesMatcher::class);
        $brandsByKey = BrandVehicle::all()->keyBy(
            fn (BrandVehicle $b) => $matcher->normalize($matcher->canonicalBrandName($b->name)),
        );
        $modelsByBrand = ModelVehicle::all()
            ->groupBy('brand_vehicle_id')
            ->map(fn ($group) => $group->keyBy(fn (ModelVehicle $m) => $matcher->normalize($m->name)));

        $saved = 0; $unresolved = []; $seen = [];

        while (($r = fgetcsv($handle)) !== false) {
            if (count($r) < 8 && trim(implode('', $r)) === '') continue;

            $gabungan = trim(preg_replace('/\s+/', ' ', (string) $r[$iG]));
            $brand = trim((string) $r[$iB]);
            $model = trim((string) $r[$iM]);
            $type = trim((string) $r[$iT]);
            if ($gabungan === '') $gabungan = trim("$brand $model $type");
            if ($gabungan === '' || isset($seen[$gabungan])) continue;
            $seen[$gabungan] = 1;

            $brandVehicle = $brandsByKey[$matcher->normalize($matcher->canonicalBrandName($brand))] ?? null;
            $modelVehicle = $brandVehicle !== null
                ? ($modelsByBrand[$brandVehicle->id][$matcher->normalize($model)] ?? null)
                : null;
            $typeVehicle = null;
            if ($modelVehicle !== null && $type !== '') {
                $typeVehicle = TypeVehicle::query()
                    ->where('model_vehicle_id', $modelVehicle->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($type)])->first();
            }

            $fuel = strtoupper(trim((string) $r[$iF]));
            $pt = strtoupper(trim((string) $r[$iP]));
            $category = trim((string) $r[$iC]);
            $size = trim((string) $r[$iS]);

            VehicleConnecting::updateOrCreate(
                ['raw_gabungan' => $gabungan],
                [
                    'fuel' => $fuel !== '' ? $fuel : null,
                    'brand_vehicle_id' => $brandVehicle?->id,
                    'model_vehicle_id' => $modelVehicle?->id,
                    'type_vehicle_id' => $typeVehicle?->id,
                    'powertrain' => $pt !== '' ? $pt : null,
                    'category' => $category !== '' ? $category : null,
                    'size_class' => $size !== '' ? $size : null,
                ],
            );

            if ($brandVehicle === null || $modelVehicle === null) {
                $unresolved[] = "$brand | $model";
            } else {
                $saved++;
            }
        }
        fclose($handle);

        return ['saved' => $saved + count($unresolved), 'unresolved' => array_slice($unresolved, 0, 15)];
    }

    /**
     * Kategori/ukuran dari CSV diterapkan ke model katalog existing.
     *
     * @return array{updated: int, notFound: list<string>}
     */
    public function backfillCategories(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("File tidak bisa dibuka: {$csvPath}");
        }

        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        $idx = fn (string $n): ?int => ($i = array_search($n, $hdr, true)) === false ? null : $i;
        $iB = $idx('BRAND'); $iM = $idx('MODEL');
        $iC = $idx('CATEGORY'); $iS = $idx('SIZE');

        if ($iB === null || $iM === null) {
            fclose($handle);
            throw new \RuntimeException('Header wajib memuat kolom BRAND dan MODEL.');
        }

        $updated = 0; $notFound = []; $seen = [];

        while (($r = fgetcsv($handle)) !== false) {
            if (count($r) < 6 && trim(implode('', $r)) === '') continue;

            $brand = trim((string) ($r[$iB] ?? ''));
            $model = trim((string) ($r[$iM] ?? ''));
            $category = \App\Support\VehicleCategories::normalizeCategory($r[$iC] ?? null);
            $size = \App\Support\VehicleCategories::normalizeSize($r[$iS] ?? null);

            if ($brand === '' || $model === '') continue;

            $key = mb_strtolower($brand).'|'.mb_strtolower($model);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $mv = ModelVehicle::query()
                ->join('brand_vehicles', 'brand_vehicles.id', '=', 'model_vehicles.brand_vehicle_id')
                ->whereRaw('LOWER(TRIM(brand_vehicles.name)) = ?', [mb_strtolower($brand)])
                ->whereRaw('LOWER(TRIM(model_vehicles.name)) = ?', [mb_strtolower($model)])
                ->first(['model_vehicles.*']);

            if ($mv === null) {
                $notFound[] = "$brand $model";
                continue;
            }

            $attributes = [];
            if ($category !== null) $attributes['category'] = $category;
            if ($size !== null) $attributes['size_class'] = $size;
            if ($attributes !== [] && $mv->fill($attributes)->isDirty()) {
                $mv->save();
                $updated++;
            }
        }
        fclose($handle);

        return ['updated' => $updated, 'notFound' => $notFound];
    }

    public function flushMarketCache(): void
    {
        app(\App\Services\VehicleMarketService::class)->flush();
    }
}
