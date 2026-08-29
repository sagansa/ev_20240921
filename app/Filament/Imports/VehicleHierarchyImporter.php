<?php

namespace App\Filament\Imports;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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

        if ($brandName === '' || $modelName === '') {
            throw ValidationException::withMessages([
                'BRAND' => ['Kolom BRAND dan MODEL wajib terisi pada setiap baris.'],
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

        $model ??= ModelVehicle::query()->create([
            'name' => $modelName,
            'brand_vehicle_id' => $brand->getKey(),
        ]);

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
