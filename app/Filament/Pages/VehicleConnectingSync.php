<?php

namespace App\Filament\Pages;

use App\Services\VehicleConnectingComparer;
use App\Services\VehicleConnectingSyncService;
use Filament\Pages\Page;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Alur update katalog lewat CSV — 3 langkah terpisah agar jelas mana
 * UPDATE dan mana ADD:
 *  1. Verifikasi    : bandingkan CSV vs katalog (dry-run, tanpa menulis)
 *  2. Import CSV    : CSV → tabel vehicle_connectings (master mapping,
 *                     kunci unik raw_gabungan — duplikat tidak mungkin)
 *  3. Terapkan      : turunkan connecting → brand/model/type katalog
 *                     (entitas baru dibuat, klasifikasi konsisten diterapkan)
 * Diakhiri flush cache Pasar EV.
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

    /** Langkah 2: CSV → tabel vehicle_connectings (master). */
    public function importConnecting(): void
    {
        $this->validate(['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480']]);

        try {
            $res = app(VehicleConnectingSyncService::class)->importConnectingTable($this->csvFile->getRealPath());
            $this->log[] = 'Connecting: '.$res['saved'].' baris tersimpan'.
                (count($res['unresolved']) > 0
                    ? ' | link katalog belum lengkap: '.implode('; ', array_slice($res['unresolved'], 0, 5))
                    : ' | semua ter-link ke katalog');
        } catch (RuntimeException $e) {
            $this->log[] = '✗ Gagal: '.$e->getMessage();
        }
    }

    /** Langkah 3: turunkan connecting → katalog + flush cache. */
    public function applyToCatalog(): void
    {
        try {
            $res = app(VehicleConnectingSyncService::class)->applyToCatalog();
            $this->log[] = 'Katalog diterapkan: +'.$res['brands'].' brand, +'.$res['models'].' model, +'.
                $res['types'].' type, '.$res['categoriesUpdated'].' klasifikasi diperbarui'.
                (count($res['conflicts']) > 0
                    ? ' | '.count($res['conflicts']).' konflik nilai campuran dibiarkan (lihat log)' : '');
            foreach (array_slice($res['conflicts'], 0, 10) as $c) {
                $this->log[] = '  ⚠ konflik '.$c['field'].': '.$c['brand'].' / '.$c['model'].' — '.$c['values'];
            }
            app(VehicleConnectingSyncService::class)->flushMarketCache();
            $this->log[] = 'Cache Pasar EV di-flush.';
        } catch (RuntimeException $e) {
            $this->log[] = '✗ Gagal: '.$e->getMessage();
        }
    }
}
