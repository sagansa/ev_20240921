<?php

namespace App\Filament\Pages;

use App\Services\VehicleSalesPreviewService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Halaman "Preview Impor Penjualan" — GUI dari vehicle-sales:preview:
 * upload CSV laporan GAIKINDO → lihat kombinasi brand/model yang BELUM ada
 * di katalog base → unduh CSV siap gabung ke CONNECTING. Read-only, tidak
 * pernah menulis ke stats/katalog.
 */
class VehicleSalesPreviewImport extends Page
{
    use WithFileUploads;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-document-magnifying-glass';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?string $navigationLabel = 'Preview Impor Penjualan';

    protected static ?string $title = 'Preview Impor Penjualan vs Katalog';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.vehicle-sales-preview-import';

    /** Upload sementara (Livewire temporary upload). */
    public $csvFile;

    #[Url]
    public ?int $month = null;

    public ?array $result = null;

    public ?string $exportPath = null;

    public ?string $error = null;

    /** Form simpan mapping eksplisit dari baris "baru". */
    public ?string $mapRawBrand = null;

    public ?string $mapRawModel = null;

    public ?string $mapBrandName = null;

    public ?string $mapModelName = null;

    public ?string $mapCatatan = null;

    public ?string $mapMessage = null;

    /** Form inline per baris kombinasi baru: category/size/powertrain. */
    public array $newRowForms = [];

    /** Indeks baris yang sudah masuk CONNECTING. */
    public array $newRowSaved = [];

    public ?string $newRowMessage = null;

    /** Toggle tampil/sembunyi detail baris yang dilewati (junk). */
    public bool $showSkipped = false;

    public function toggleSkipped(): void
    {
        $this->showSkipped = ! $this->showSkipped;
    }

    /** @return list<string> */
    public static function categoryOptions(): array
    {
        return \App\Support\VehicleCategories::CATEGORIES;
    }

    /** Tambahkan satu kombinasi baru langsung ke tabel CONNECTING. */
    public function addToConnecting(int $index): void
    {
        $row = $this->result['new'][$index] ?? null;

        if ($row === null) {
            return;
        }

        $form = $this->newRowForms[$index] ?? [];
        $powertrain = strtoupper(trim((string) ($form['powertrain'] ?? ''))) ?: $row['powertrain'];
        $category = \App\Support\VehicleCategories::normalizeCategory($form['category'] ?? null);
        $size = \App\Support\VehicleCategories::normalizeSize($form['size'] ?? null);

        if ($category === null) {
            $this->newRowMessage = "✗ '{$row['brand']} {$row['model']}': pilih kategori dulu.";

            return;
        }

        if (! in_array($category, \App\Support\VehicleCategories::SIZABLE, true)) {
            $size = null; // size hanya untuk kategori ber-ukuran
        }

        $brand = trim((string) $row['brand']);
        $model = trim((string) $row['model']);
        $type = trim((string) $row['type']);
        $gabungan = trim(preg_replace('/\s+/', ' ', "$brand $model $type"));
        $key = preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper($gabungan));

        // Link ke katalog bila sudah ada (brand/model baru dibuat nanti oleh
        // "Terapkan ke Katalog" di halaman Sync CONNECTING).
        $matcher = app(\App\Services\VehicleSalesMatcher::class);
        $brandVehicle = \App\Models\BrandVehicle::query()->get()
            ->first(fn ($b) => $matcher->normalize($matcher->canonicalBrandName($b->name))
                === $matcher->normalize($matcher->canonicalBrandName($brand)));
        $modelVehicle = $brandVehicle !== null
            ? \App\Models\ModelVehicle::where('brand_vehicle_id', $brandVehicle->id)->get()
                ->first(fn ($m) => $matcher->normalize($m->name) === $matcher->normalize($model))
            : null;
        $typeVehicle = ($modelVehicle !== null && $type !== '')
            ? \App\Models\TypeVehicle::where('model_vehicle_id', $modelVehicle->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($type)])->first()
            : null;

        \App\Models\VehicleConnecting::updateOrCreate(
            ['raw_gabungan_key' => $key],
            [
                'raw_gabungan' => $gabungan,
                'brand_name' => $brand,
                'model_name' => $model,
                'type_name' => $type !== '' ? $type : null,
                'brand_vehicle_id' => $brandVehicle?->id,
                'model_vehicle_id' => $modelVehicle?->id,
                'type_vehicle_id' => $typeVehicle?->id,
                'powertrain' => $powertrain !== '' ? $powertrain : null,
                'category' => $category,
                'size_class' => $size,
            ],
        );

        $this->newRowSaved[$index] = true;
        $this->newRowMessage = "✓ '{$gabungan}' masuk CONNECTING ({$category}".($size !== null ? " / {$size}" : '').').'.
            ' Lanjutkan: halaman Sync CONNECTING → Terapkan ke Katalog → Import Penjualan.';
    }

    public function saveMapping(): void
    {
        $this->validate([
            'mapRawBrand' => ['required', 'string', 'max:255'],
            'mapRawModel' => ['required', 'string', 'max:255'],
            'mapBrandName' => ['required', 'string', 'max:255'],
            'mapModelName' => ['required', 'string', 'max:255'],
        ], [
            'mapRawBrand.required' => 'Raw brand wajib diisi.',
            'mapRawModel.required' => 'Raw model wajib diisi.',
            'mapBrandName.required' => 'Brand katalog wajib diisi.',
            'mapModelName.required' => 'Model katalog wajib diisi.',
        ]);

        $mapping = \App\Models\VehicleNameMapping::record(
            $this->mapRawBrand,
            $this->mapRawModel,
            $this->mapBrandName,
            $this->mapModelName,
            null,
            $this->mapCatatan,
        );

        if ($mapping === null) {
            $this->mapMessage = "✗ Katalog '{$this->mapBrandName} / {$this->mapModelName}' belum ada — buat dulu lewat Brand Vehicles → Import.";

            return;
        }

        $this->mapMessage = '✓ Mapping tersimpan — laporan berikutnya otomatis ter-link. Untuk stats lama, jalankan vehicle-mapping:relink.';
        $this->mapRawBrand = $this->mapRawModel = $this->mapBrandName = $this->mapModelName = $this->mapCatatan = null;

        // Segarkan hasil analisis dengan mapping baru.
        if ($this->csvFile !== null) {
            try {
                $this->result = app(VehicleSalesPreviewService::class)->analyze(
                    $this->csvFile->getRealPath(),
                    $this->month,
                );
            } catch (RuntimeException) {
                // biarkan hasil lama
            }
        }
    }

    public function analyze(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ], [
            'csvFile.required' => 'Pilih file CSV laporan terlebih dahulu.',
            'csvFile.mimes' => 'File harus berformat .csv',
        ]);

        $this->error = null;
        $this->exportPath = null;
        $this->newRowForms = [];
        $this->newRowSaved = [];
        $this->newRowMessage = null;
        $this->showSkipped = false;

        try {
            $this->result = app(VehicleSalesPreviewService::class)->analyze(
                $this->csvFile->getRealPath(),
                $this->month,
            );
        } catch (RuntimeException $e) {
            $this->result = null;
            $this->error = $e->getMessage();
        }
    }

    /** Unduh kombinasi baru sebagai CSV siap gabung ke CONNECTING. */
    public function downloadNew(): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if ($this->result === null || $this->result['new'] === []) {
            return null;
        }

        $path = 'previews/connecting-baru-'.now()->format('Ymd-His').'.csv';
        Storage::disk('local')->makeDirectory(dirname($path));
        app(VehicleSalesPreviewService::class)->exportNewCsv(
            $this->result['new'],
            Storage::disk('local')->path($path),
        );

        $this->exportPath = $path;

        return Storage::disk('local')->download($path, 'CONNECTING-baru.csv');
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }
}
