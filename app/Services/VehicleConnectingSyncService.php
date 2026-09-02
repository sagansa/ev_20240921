<?php

namespace App\Services;

use App\Filament\Imports\VehicleHierarchyImporter;
use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\Vehicle;
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
     * TURUNKAN isi tabel connecting → katalog (brand/model/type). Connecting
     * adalah SUMBER KEBENARAN: entitas baru dibuat dari sini; klasifikasi
     * model diperbarui hanya bila nilai connecting KONSISTEN (seragam) —
     * keluarga dengan nilai campuran dilaporkan, tidak ditimpa asal-asalan.
     *
     * @return array{brands: int, models: int, types: int, categoriesUpdated: int,
     *               conflicts: list<array{brand: string, model: string, field: string, values: string}>}
     */
    public function applyToCatalog(): array
    {
        $norm = fn (?string $v): string => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)) ?? '');

        $rows = VehicleConnecting::whereNotNull('brand_name')->whereNotNull('model_name')->get();

        // Group per keluarga (brand + model).
        $groups = [];
        foreach ($rows as $row) {
            $bKey = $norm($row->brand_name);
            $mKey = $bKey.'|'.$norm($row->model_name);
            $g = $groups[$mKey] ??= [
                'brand_name' => $row->brand_name,
                'model_name' => $row->model_name,
                'rows' => [],
            ];
            $g['rows'][] = $row;
            $groups[$mKey] = $g;
        }

        $brands = BrandVehicle::all()->keyBy(fn (BrandVehicle $b) => $norm($b->name));
        $models = ModelVehicle::all()->keyBy(fn (ModelVehicle $m) => $norm($m->brandVehicle?->name ?? '').'|'.$norm($m->name));

        $stats = ['brands' => 0, 'models' => 0, 'types' => 0, 'categoriesUpdated' => 0];
        $conflicts = [];

        foreach ($groups as $g) {
            $bKey = $norm($g['brand_name']);
            $mKey = $bKey.'|'.$norm($g['model_name']);

            $brand = $brands[$bKey] ?? null;
            if ($brand === null) {
                $brand = BrandVehicle::create(['name' => $g['brand_name']]);
                $brands[$bKey] = $brand;
                $stats['brands']++;
            }

            $model = $models[$mKey] ?? null;
            if ($model === null) {
                // Nilai klasifikasi: ambil yang seragam dari connecting.
                $category = $this->uniform($g['rows'], 'category');
                $size = $this->uniform($g['rows'], 'size_class');

                $model = ModelVehicle::create([
                    'name' => $g['model_name'],
                    'brand_vehicle_id' => $brand->id,
                    'category' => $category,
                    'size_class' => $size,
                ]);
                $models[$mKey] = $model;
                $stats['models']++;
            } else {
                // Model existing: terapkan nilai yang SERAGAM; campuran dilaporkan.
                foreach (['category', 'size_class'] as $field) {
                    $values = collect($g['rows'])->map(fn ($r) => $r->$field)
                        ->filter(fn ($v) => $v !== null && $v !== '')->unique()->values();

                    if ($values->count() === 1 && $values->first() !== $model->$field) {
                        $model->$field = $values->first();
                        $stats['categoriesUpdated']++;
                    } elseif ($values->count() > 1) {
                        $conflicts[] = [
                            'brand' => $g['brand_name'], 'model' => $g['model_name'],
                            'field' => $field, 'values' => $values->implode(' vs '),
                        ];
                    }
                }
                if ($model->isDirty()) {
                    $model->save();
                }
            }

            // Type: pastikan ada (firstOrCreate by nama, model sama).
            foreach ($g['rows'] as $row) {
                if (($row->type_name ?? '') === '') continue;
                $tKey = $norm($row->type_name);
                $exists = TypeVehicle::query()
                    ->where('model_vehicle_id', $model->id)
                    ->get()
                    ->first(fn (TypeVehicle $t) => $norm($t->name) === $tKey);
                if ($exists === null) {
                    TypeVehicle::create([
                        'name' => $row->type_name,
                        'model_vehicle_id' => $model->id,
                        'type_charger' => [],
                        'powertrain' => $row->powertrain,
                    ]);
                    $stats['types']++;
                }
            }
        }

        return $stats + ['conflicts' => $conflicts];
    }

    /** Nilai seragam dr sekumpulan baris connecting: sama semua → nilai; campuran → null + konflik. */
    protected function uniform($rows, string $field): ?string
    {
        $values = collect($rows)->map(fn ($r) => $r->$field)->filter(fn ($v) => $v !== null && $v !== '')->unique()->values();
        if ($values->isEmpty()) return null;
        if ($values->count() > 1) {
            return null; // campuran — kategori/powertrain keluarga ambigu
        }
        return $values->first();
    }

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
    public function importConnectingTable(string $csvPath, bool $prune = false): array
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

        $this->backfillKeys();

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
                ['raw_gabungan_key' => preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper($gabungan))],
                [
                    'raw_gabungan' => $gabungan,
                    'fuel' => $fuel !== '' ? $fuel : null,
                    'brand_name' => $brand,
                    'model_name' => $model,
                    'type_name' => $type !== '' ? $type : null,
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

        $pruned = 0;
        if ($prune) {
            $pruned = VehicleConnecting::whereNotIn('raw_gabungan', array_keys($seen))->delete();
        }

        return ['saved' => $saved + count($unresolved), 'unresolved' => array_slice($unresolved, 0, 15), 'pruned' => $pruned];
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

    /**
     * Pulihkan raw_gabungan_key baris yang masih kosong (diimpor sebelum
     * kolom ini ada / lewat jalur lama yang tidak mengisinya) — tanpa ini,
     * pencocokan berbasis key meleset.
     *
     * @return int jumlah baris yang diisi
     */
    public function backfillKeys(): int
    {
        $filled = 0;

        VehicleConnecting::query()->whereNull('raw_gabungan_key')
            ->chunkById(500, function ($rows) use (&$filled) {
                foreach ($rows as $r) {
                    $k = preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper((string) $r->raw_gabungan));

                    if ($k === '' || $k === null) {
                        continue;
                    }

                    // Nama berbeda bisa squash ke key sama ("X-55 II" vs
                    // "X55 II") — unique index menolak. Lewati; baris itu
                    // tetap tercocokkan lewat fallback raw teks di matcher.
                    if (VehicleConnecting::where('raw_gabungan_key', $k)->whereKeyNot($r->getKey())->exists()) {
                        continue;
                    }

                    $r->forceFill(['raw_gabungan_key' => $k])->save();
                    $filled++;
                }
            });

        return $filled;
    }

    public function flushMarketCache(): void
    {
        app(\App\Services\VehicleMarketService::class)->flush();
    }

    /**
     * PRUNE KATALOG — hapus brand/model/type yang TIDAK direferensikan
     * CONNECTING (kasus: duplikat hasil auto-create impor lama, mis.
     * "MITSUBISHI FUSO" sebelum alias dipasang).
     *
     * Pengaman: model hanya dihapus bila TIDAK dipakai user kendaraan,
     * TIDAK punya stats penjualan, dan TIDAK punya type — sisanya dilaporkan
     * agar dibereskan lewat jalur lain (alias/mapping + re-import).
     *
     * @return array{deletedModels: int, deletedBrands: list<string>, kept: list<array{brand: string, model: string, vehicles: int, stats: int, types: int}>}
     */
    public function pruneCatalog(string $csvPath): array
    {
        $refs = $this->referencedKeys($csvPath);

        // ===== 1. TYPE (berjenjang paling bawah) =====
        // Hapus type yang tidak direferensikan CONNECTING, KECUALI type itu
        // masih dipakai kendaraan user (vehicle.type_vehicle_id).
        $typesDeleted = 0;
        $typesExempt = [];
        foreach (TypeVehicle::with('modelVehicle.brandVehicle')->get() as $type) {
            $bName = $type->modelVehicle?->brandVehicle?->name;
            $mName = $type->modelVehicle?->name;
            $tKey = $this->normKey($bName).'|'.$this->normKey($mName).'|'.$this->normKey($type->name);

            if (isset($refs['types'][$tKey])) continue;

            if (Vehicle::where('type_vehicle_id', $type->id)->exists()) {
                $typesExempt[] = ($bName ?: '?').' / '.($mName ?: '?').' / '.$type->name;
                continue;
            }

            $type->delete();
            $typesDeleted++;
        }

        // ===== 2. MODEL =====
        // Hapus model yang tidak direferensikan CONNECTING, KECUALI masih
        // dimiliki user. Stats yang menempel dilepas (raw tetap tersimpan
        // utk agregat pasar) dan dilaporkan.
        $modelsDeleted = 0;
        $modelsExempt = [];
        $statsDetached = 0;
        foreach (ModelVehicle::with('brandVehicle')->get() as $model) {
            $mKey = $this->normKey($model->brandVehicle?->name).'|'.$this->normKey($model->name);

            if (isset($refs['models'][$mKey])) continue;

            $inUse = Vehicle::where('model_vehicle_id', $model->id)->count();
            if ($inUse > 0) {
                $modelsExempt[] = [
                    'brand' => $model->brandVehicle?->name ?? '?',
                    'model' => $model->name,
                    'vehicles' => $inUse,
                ];
                continue;
            }

            $statsDetached += VehicleSalesStat::where('model_vehicle_id', $model->id)
                ->update(['model_vehicle_id' => null, 'type_vehicle_id' => null]);

            TypeVehicle::where('model_vehicle_id', $model->id)->delete();
            $model->delete();
            $modelsDeleted++;
        }

        // ===== 3. BRAND =====
        $brandsDeleted = [];
        foreach (BrandVehicle::doesntHave('modelVehicles')->get() as $brand) {
            $brandsDeleted[] = $brand->name;
            $brand->delete();
        }

        return [
            'typesDeleted' => $typesDeleted,
            'typesExempt' => $typesExempt,
            'modelsDeleted' => $modelsDeleted,
            'modelsExempt' => $modelsExempt,
            'statsDetached' => $statsDetached,
            'brandsDeleted' => $brandsDeleted,
        ];
    }

    /**
     * Kumpulan kunci referensi dari CSV: models[(canon brand|norm model)]
     * dan types[(model key)|norm type].
     *
     * @return array{models: array<string, true>, types: array<string, true>}
     */
    /** Kunci squash: huruf+angka saja, uppercase — kebal spasi/kapitalisasi/tanda baca. */
    protected function normKey(?string $v): string
    {
        return preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper($v ?? ''));
    }

    public function referencedKeys(string $csvPath): array
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("File tidak bisa dibuka: {$csvPath}");
        }

        $hdr = array_map(fn ($c) => strtoupper(trim((string) $c)), fgetcsv($handle) ?: []);
        foreach (['BRAND', 'MODEL'] as $need) {
            if (! in_array($need, $hdr, true)) {
                fclose($handle);
                throw new \RuntimeException("Header wajib memuat kolom: {$need}");
            }
        }
        $iB = array_search('BRAND', $hdr, true);
        $iM = array_search('MODEL', $hdr, true);
        $iT = array_search('TYPE', $hdr, true) ?? $iM;

        $matcher = app(VehicleSalesMatcher::class);

        $models = []; $types = [];
        while (($r = fgetcsv($handle)) !== false) {
            if (count($r) < 6 && trim(implode('', $r)) === '') continue;
            $brand = trim((string) $r[$iB]);
            $model = trim((string) $r[$iM]);
            $type = trim((string) $r[$iT]);
            if ($brand === '' || $model === '') continue;

            $bKey = $this->normKey($matcher->canonicalBrandName($brand));
            $mKey = $bKey.'|'.$this->normKey($model);
            $models[$mKey] = true;
            if ($type !== '') {
                $types[$mKey.'|'.$this->normKey($type)] = true;
            }
        }
        fclose($handle);

        return ['models' => $models, 'types' => $types];
    }
}
