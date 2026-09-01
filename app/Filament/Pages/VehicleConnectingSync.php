<?php

namespace App\Filament\Pages;

use App\Services\VehicleConnectingComparer;
use App\Services\VehicleConnectingSyncService;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Halaman "Sinkronisasi CONNECTING" — GUI alur update lewat CSV:
 * upload → Verifikasi (dry-run) → Jalankan Sinkronisasi
 * (tabel connecting → katalog → backfill kategori → flush cache pasar).
 */
class VehicleConnectingSync extends Page
{
    use WithFileUploads;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-m-arrow-path';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?string $navigationLabel = 'Sinkronisasi CONNECTING';

    protected static ?string $title = 'Sinkronisasi CONNECTING ke Katalog';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.vehicle-connecting-sync';

    public $csvFile;

    public ?array $report = null;

    public ?array $syncResult = null;

    /** @var list<string> */
    public array $log = [];

    public ?string $error = null;

    public function verify(): void
    {
        $this->validate(['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480']],
            ['csvFile.required' => 'Pilih file CONNECTING terlebih dahulu.']);

        $this->error = null;
        try {
            $this->report = app(VehicleConnectingComparer::class)->compare($this->csvFile->getRealPath());
        } catch (RuntimeException $e) {
            $this->report = null;
            $this->error = $e->getMessage();
        }
    }

    public function sync(): void
    {
        $this->validate(['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480']]);

        $this->error = null;
        $this->log = [];
        $path = $this->csvFile->getRealPath();
        $svc = app(VehicleConnectingSyncService::class);

        try {
            $table = $svc->importConnectingTable($path);
            $this->log[] = 'Tabel connecting: '.$table['saved'].' baris tersimpan'.
                (count($table['unresolved']) > 0
                    ? ', '.count($table['unresolved']).' link katalog belum lengkap'
                    : ', semua ter-link');

            $catalog = $svc->importCatalog($path);
            $this->log[] = 'Katalog: '.$catalog['processed'].' baris diproses, +'.
                $catalog['brands'].' brand, +'.$catalog['models'].' model, +'.$catalog['types'].' type'.
                (count($catalog['failed']) > 0 ? ', '.count($catalog['failed']).' gagal' : '');

            $backfill = $svc->backfillCategories($path);
            $this->log[] = 'Kategori model diperbarui: '.$backfill['updated'].
                ' (tidak ada di katalog: '.count($backfill['notFound']).')';

            $svc->flushMarketCache();
            $this->log[] = 'Cache Pasar EV di-flush.';

            $this->syncResult = [
                'table' => $table,
                'catalog' => $catalog,
                'backfill' => ['updated' => $backfill['updated']],
            ];
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();
        }
    }
}
