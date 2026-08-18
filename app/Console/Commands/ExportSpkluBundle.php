<?php

namespace App\Console\Commands;

use App\Models\ChargingStation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Ekspor data canonical terkini (charging_stations + relasi provider/chargerBoxes)
 * ke format bundle offline yang dipakai Android/iOS saat startup & gagal network.
 *
 * Hasilnya menimpa asset:
 * - mobile/androidApp/src/main/assets/spklu_data.json
 * - mobile/iosApp/iosApp/spklu_data.json
 *
 * Bundle memakai kunci lama (`chargerboxes`, dsb) yang dikenali loader kedua
 * platform, DITAMBAH field real-time (availability_level, available_count,
 * total_konektor, dst) supaya pin offline tidak lagi jatuh ke mode ghost
 * transparan dan logo HVT tidak salah label "PLN Mobile".
 */
class ExportSpkluBundle extends Command
{
    protected $signature = 'spklu:export-bundle
        {--android= : Path output Android (default repo mobile/androidApp/src/main/assets/spklu_data.json)}
        {--ios= : Path output iOS (default repo mobile/iosApp/iosApp/spklu_data.json)}
        {--url= : Base URL absolut utk provider_logo (default: APP_URL — bundle iOS memakai URL ini langsung di AsyncImage)}';

    protected $description = 'Export canonical SPKLU ke bundle offline (Android & iOS assets).';

    public function handle(): int
    {
        $androidPath = $this->option('android') ?? $this->defaultRepoPath('mobile/androidApp/src/main/assets/spklu_data.json');
        $iosPath = $this->option('ios') ?? $this->defaultRepoPath('mobile/iosApp/iosApp/spklu_data.json');
        $baseUrl = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        if ($baseUrl === '' || ! preg_match('#^https?://#', $baseUrl)) {
            $this->error('Base URL tidak valid — set APP_URL atau gunakan --url=https://domain');

            return 1;
        }

        $this->info('Export bundle dari charging_stations (source='.config('spklu.serving_source').')...');

        $stations = ChargingStation::with(['provider', 'chargerBoxes'])
            ->where('source', config('spklu.serving_source'))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->get();

        if ($stations->isEmpty()) {
            $this->error('Tidak ada stasiun untuk di-export.');

            return 1;
        }

        $bundle = $stations->map(fn (ChargingStation $s) => $this->toBundleItem($s, $baseUrl))->values();

        $json = json_encode(
            $bundle,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS
        )."\n";

        if ($json === false) {
            $this->error('Gagal encode JSON: '.json_last_error_msg());

            return 1;
        }

        $written = 0;
        foreach (['Android' => $androidPath, 'iOS' => $iosPath] as $label => $path) {
            $dir = dirname($path);
            if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                $this->error("[$label] Direktori tidak bisa dibuat: $dir");
                continue;
            }
            if (file_put_contents($path, $json) === false) {
                $this->error("[$label] Gagal menulis: $path");
                continue;
            }
            $written++;
            $this->info("[$label] ✓ $path (".number_format(strlen($json)).' bytes, '.$stations->count().' item)');
        }

        $withLevel = $stations->filter(fn ($s) => ! empty($s->availability_level))->count();
        $withProvider = $stations->filter(fn ($s) => ! empty($s->provider?->name))->count();

        $this->newLine();
        $this->info("Summary: $withLevel/{$stations->count()} punya availability_level; $withProvider/{$stations->count()} punya provider ter-resolve.");

        return $written === 2 ? 0 : 1;
    }

    private function toBundleItem(ChargingStation $s, string $baseUrl): array
    {
        $provider = $s->provider;

        $boxes = $s->chargerBoxes->map(function ($box) {
            $item = [
                'chargerbox_id' => $box->chargerbox_id,
                'type_charge' => $box->type_charge,
                'nama_chargerbox' => $box->nama_chargerbox ?? $box->nama,
                'watt' => $box->watt,
                'jumlah_charger' => (int) ($box->jumlah_charger ?? 1),
                'jumlah_konektor' => (string) ($box->jumlah_konektor ?? '1'),
                'icon' => $box->icon,
                'gambar' => $box->gambar,
                'availability_level' => $box->availability_level,
                'available_count' => (int) ($box->available_count ?? 0),
                'charging_count' => (int) ($box->charging_count ?? 0),
                'finishing_count' => (int) ($box->finishing_count ?? 0),
                'status_updated_at' => $box->status_updated_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
            ];

            return $item;
        })->values()->all();

        return [
            'id' => (int) ($s->external_id ?? $s->id),
            'provinsi' => $s->provinsi,
            'kabupaten_kota' => $s->kabupaten_kota,
            'nama_lokasi' => $s->nama_lokasi,
            'alamat' => $s->alamat,
            'keterangan' => $s->keterangan,
            'status' => (int) ($s->status ?? 1),
            'latitude' => (float) $s->latitude,
            'longitude' => (float) $s->longitude,
            'type_charge' => $s->type_charge,
            'watt' => $s->watt,
            'toll_category' => $s->toll_category ?? $s->kategori_tol,
            'location_category' => $s->location_category ?? $s->kategori_lokasi,
            'kategori_tol' => $s->kategori_tol ?? $s->toll_category,
            'kategori_lokasi' => $s->kategori_lokasi ?? $s->location_category,
            'total_charger' => (int) $s->total_charger,
            'total_konektor' => (int) $s->total_konektor,
            // Status real-time agregat — dipakai pin offline (avoid ghost).
            'availability_level' => $s->availability_level,
            'available_count' => (int) $s->available_count,
            'charging_count' => (int) $s->charging_count,
            'finishing_count' => (int) $s->finishing_count,
            'status_updated_at' => $s->status_updated_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
            'provider_id' => $provider?->id,
            'provider_name' => $provider?->name,
            'provider_logo' => $this->absoluteLogoUrl($provider?->logo, $baseUrl),
            'chargerboxes' => $boxes,
        ];
    }

    /** Logo relatif (/storage/...) dijadikan absolut — loader bundle iOS
     *  memakai URL ini langsung; Android match nama file utk asset lokal. */
    private function absoluteLogoUrl(?string $path, string $baseUrl): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : $baseUrl.$path;
    }

    /** Lokasi asset di repo mobile (default bila dipanggil dari direktori backend). */
    private function defaultRepoPath(string $relative): string
    {
        $backendDir = realpath(__DIR__.'/../../..');
        if ($backendDir && basename($backendDir) === 'backend') {
            return dirname($backendDir).'/'.$relative;
        }

        return base_path($relative);
    }
}