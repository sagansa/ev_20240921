<?php

namespace App\Filament\Pages;

use App\Models\BrandVehicle;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\VehicleConnecting;
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

    protected static ?string $title = 'Sinkronisasi CONNECTING ke Master Katalog';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.vehicle-connecting-sync';

    public $csvFile;

    public ?array $report = null;

    /** @var list<string> */
    public array $log = [];

    public ?string $error = null;

    public string $activeTab = 'new';

    public string $searchQuery = '';

    public string $selectedPowertrain = 'ALL';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function removeFile(): void
    {
        $this->csvFile = null;
        $this->report = null;
        $this->error = null;
    }

    public function clearReport(): void
    {
        $this->report = null;
        $this->error = null;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }

    public function verify(): void
    {
        $this->validate(
            ['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480']],
            ['csvFile.required' => 'Pilih file CONNECTING terlebih dahulu.']
        );

        $this->error = null;
        try {
            $this->report = app(VehicleConnectingComparer::class)->compare($this->csvFile->getRealPath());
            
            // Set default tab based on findings
            if (!empty($this->report['brandBaru']) || !empty($this->report['modelBaru']) || !empty($this->report['typeBaru'])) {
                $this->activeTab = 'new';
            } elseif (!empty($this->report['klasifikasiBeda'])) {
                $this->activeTab = 'diff';
            } elseif (!empty($this->report['csvTidakKonsisten'])) {
                $this->activeTab = 'inconsistent';
            } else {
                $this->activeTab = 'match';
            }
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
            $time = now()->format('H:i:s');
            $this->log[] = "[{$time}] 💾 Master Connecting: {$res['saved']} baris berhasil disimpan".
                (count($res['unresolved']) > 0
                    ? ' | link katalog belum lengkap: '.implode('; ', array_slice($res['unresolved'], 0, 5))
                    : ' | semua ter-link ke katalog');
        } catch (RuntimeException $e) {
            $time = now()->format('H:i:s');
            $this->log[] = "[{$time}] ❌ Gagal Simpan Connecting: ".$e->getMessage();
        }
    }

    /** Prune: hapus brand/model katalog yang tidak direferensikan CONNECTING. */
    public function pruneCatalog(): void
    {
        $this->validate(['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:20480']]);

        try {
            $res = app(VehicleConnectingSyncService::class)->pruneCatalog($this->csvFile->getRealPath());
            $this->log[] = 'Prune: '.$res['modelsDeleted'].' model dihapus, '.$res['typesDeleted'].
                ' type terkait dihapus, '.$res['statsDetached'].' baris stats dilepas ke raw.';
            foreach (array_slice($res['modelsExempt'], 0, 10) as $m) {
                $this->log[] = '  ⚠ dipertahankan (dipakai user): '.$m['brand'].' / '.$m['model'].
                    ' — '.$m['vehicles'].' kendaraan';
            }
            app(VehicleConnectingSyncService::class)->flushMarketCache();
            $this->log[] = 'Cache Pasar EV di-flush.';
        } catch (RuntimeException $e) {
            $this->log[] = '✗ Gagal: '.$e->getMessage();
        }
    }

    /** Langkah 3: turunkan connecting → katalog + flush cache. */
    public function applyToCatalog(): void
    {
        try {
            $res = app(VehicleConnectingSyncService::class)->applyToCatalog();
            $time = now()->format('H:i:s');
            $this->log[] = "[{$time}] ⚡ Katalog Diterapkan: +{$res['brands']} brand, +{$res['models']} model, +".
                "{$res['types']} type, {$res['categoriesUpdated']} klasifikasi diperbarui".
                (count($res['conflicts']) > 0
                    ? ' | '.count($res['conflicts']).' konflik nilai campuran dibiarkan (lihat detail di bawah)' : '');
            
            foreach (array_slice($res['conflicts'], 0, 10) as $c) {
                $this->log[] = "[{$time}]   ⚠️ Konflik {$c['field']}: {$c['brand']} / {$c['model']} — {$c['values']}";
            }
            
            app(VehicleConnectingSyncService::class)->flushMarketCache();
            $this->log[] = "[{$time}] 🚀 Cache Pasar EV berhasil di-flush & diperbarui.";
        } catch (RuntimeException $e) {
            $time = now()->format('H:i:s');
            $this->log[] = "[{$time}] ❌ Gagal Menerapkan ke Katalog: ".$e->getMessage();
        }
    }

    protected function getViewData(): array
    {
        $totalConnecting = VehicleConnecting::count();
        $mappedConnecting = VehicleConnecting::whereNotNull('brand_vehicle_id')
            ->whereNotNull('model_vehicle_id')
            ->count();
        $unmappedConnecting = $totalConnecting - $mappedConnecting;
        $mappedPercentage = $totalConnecting > 0 ? round(($mappedConnecting / $totalConnecting) * 100, 1) : 100;

        return array_merge(parent::getViewData(), [
            'dbStats' => [
                'totalConnecting' => $totalConnecting,
                'mappedConnecting' => $mappedConnecting,
                'unmappedConnecting' => $unmappedConnecting,
                'mappedPercentage' => $mappedPercentage,
                'totalBrands' => BrandVehicle::count(),
                'totalModels' => ModelVehicle::count(),
                'totalTypes' => TypeVehicle::count(),
                'lastUpdated' => VehicleConnecting::latest('updated_at')->value('updated_at'),
            ],
        ]);
    }
}
