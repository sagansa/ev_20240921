<?php

namespace App\Filament\Imports;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Support\VehicleCategories;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleHierarchyImporter extends Importer
{
    protected static ?string $model = TypeVehicle::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('BRAND')
                ->label('BRAND')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('MODEL')
                ->label('MODEL')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('POWERTRAIN')
                ->label('POWERTRAIN')
                ->example('BEV')
                // Normalisasi dini agar rule `in:` kebal selisih huruf/spasi.
                ->castStateUsing(fn (mixed $state): ?string => filled($state) ? strtoupper(trim((string) $state)) : null)
                ->rules(['nullable', 'in:BEV,PHEV,HEV,ICE']),
            ImportColumn::make('CATEGORY')
                ->label('CATEGORY')
                ->example('SUV')
                // Valid → kanonik; invalid → diteruskan apa adanya agar rule
                // `Rule::in` MENOLAK barisnya (jangan didiamkan jadi null).
                ->castStateUsing(fn (mixed $state): ?string => filled($state)
                    ? (VehicleCategories::normalizeCategory((string) $state) ?? trim((string) $state))
                    : null)
                ->rules(['nullable', Rule::in(VehicleCategories::CATEGORIES)]),
            ImportColumn::make('SIZE')
                ->label('SIZE')
                ->example('Medium')
                ->castStateUsing(fn (mixed $state): ?string => filled($state)
                    ? (VehicleCategories::normalizeSize((string) $state) ?? trim((string) $state))
                    : null)
                ->rules(['nullable', Rule::in(VehicleCategories::SIZES)]),
            ImportColumn::make('TYPE')
                ->label('TYPE')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Model
    {
        $brandName = trim((string) ($this->data['BRAND'] ?? ''));
        $modelName = trim((string) ($this->data['MODEL'] ?? ''));
        $typeName = trim((string) ($this->data['TYPE'] ?? ''));
        $powertrain = filled($this->data['POWERTRAIN'] ?? null)
            ? strtoupper(trim((string) $this->data['POWERTRAIN']))
            : null;
        $category = VehicleCategories::normalizeCategory($this->data['CATEGORY'] ?? null);
        $sizeClass = VehicleCategories::normalizeSize($this->data['SIZE'] ?? null);

        if ($brandName === '' || $modelName === '') {
            throw ValidationException::withMessages([
                'BRAND' => ['Kolom BRAND dan MODEL wajib terisi pada setiap baris.'],
            ]);
        }

        // SIZE hanya bermakna untuk kategori ber-ukuran — kombinasi lain
        // ditolak agar CSV tidak menyimpan data yang tidak akan pernah dipakai.
        if ($sizeClass !== null && ($category === null || ! in_array($category, VehicleCategories::SIZABLE, true))) {
            throw ValidationException::withMessages([
                'SIZE' => ['SIZE hanya berlaku untuk kategori ber-ukuran ('.implode(', ', VehicleCategories::SIZABLE).').'],
            ]);
        }

        $brand = BrandVehicle::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($brandName)])
            ->first();

        $brand ??= BrandVehicle::query()->create(['name' => $brandName]);

        $model = ModelVehicle::query()
            ->where('brand_vehicle_id', $brand->getKey())
            ->whereRaw('LOWER(name) = ?', [Str::lower($modelName)])
            ->first();

        // Klasifikasi level model — CSV adalah sumber kebenaran: hanya
        // atribut yang disediakan CSV yang dibuat/diperbarui.
        $modelAttributes = [
            ...($powertrain !== null ? ['powertrain' => $powertrain] : []),
            ...($category !== null ? ['category' => $category] : []),
            ...($sizeClass !== null ? ['size_class' => $sizeClass] : []),
        ];

        if ($model === null) {
            $model = ModelVehicle::query()->create([
                'name' => $modelName,
                'brand_vehicle_id' => $brand->getKey(),
                // Kolom powertrain varchar(8) NOT NULL default 'ICE' — biarkan
                // default DB bila CSV tidak menyediakan nilainya.
                ...$modelAttributes,
            ]);
        } elseif ($modelAttributes !== []) {
            $model->fill($modelAttributes)->save();
        }

        if ($typeName === '') {
            // Brand + model sudah tersimpan; baris tanpa type dilewati
            // (resolveRecord boleh mengembalikan null sesuai kontrak Importer).
            return null;
        }

        return TypeVehicle::query()
            ->where('model_vehicle_id', $model->getKey())
            ->whereRaw('LOWER(name) = ?', [Str::lower($typeName)])
            ->first()
            ?? TypeVehicle::query()->make([
                'name' => $typeName,
                'model_vehicle_id' => $model->getKey(),
                // Kolom json NOT NULL pada skema existing; hanya diisi saat
                // membuat baris baru (data katalog existing tidak disentuh).
                'type_charger' => [],
            ]);
    }

    public function fillRecord(): void
    {
        // Baris yang sudah cocok tidak boleh berubah (kapitalisasi pertama yang
        // menang; nama manual dari admin tidak boleh tertimpa impor ulang).
        if ($this->record->exists) {
            return;
        }

        // Nama kolom BRAND/MODEL bukan atribut TypeVehicle — hanya nama type
        // yang diisi; kolom lain (powertrain, type_charger, dst.) tidak disentuh.
        $typeName = trim((string) ($this->data['TYPE'] ?? ''));

        if ($typeName !== '') {
            $this->record->name = $typeName;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = "Impor kendaraan selesai: {$import->successful_rows} dari {$import->total_rows} baris berhasil.";

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= " {$failedRowsCount} baris gagal — unduh CSV kegagalan untuk detailnya.";
        }

        return $body;
    }
}
