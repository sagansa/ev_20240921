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
