<?php

namespace App\Services;

use App\Models\ChargingStation;
use App\Models\ChargingStationCharger;
use App\Models\ChargingStationConnector;
use App\Models\EsdmSinggatSpkluStation;
use App\Models\EsdmSinggatStationStatus;
use App\Models\Provider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Hydrate tabel kanonik charging_stations dari source adaptor (ESDM saat ini).
 *
 * Baca raw ESDM yang sudah di-import + di-cleaning, roll-up instalasi menjadi
 * agregat per stasiun, lalu upsert ke charging_stations by (source,
 * source_station_id). Idempoten & re-runnable — source berganti tinggal
 * menambah method hydrate baru (mis. hydrateFromX()).
 *
 * Prinsip:
 *  - type_charge disimpan verbatim dari ESDM ("Medium Charging" dll).
 *  - provider: guess via ScrapeDedupService, fallback nama_badan_usaha raw.
 *  - child charging_station_chargers: replace per station.
 */
class CanonicalStationHydrateService
{
    public const SOURCE_ESDM = 'esdm';

    /** BPS kode_provinsi → nama provinsi (title case, konsisten dgn data historis). */
    public const PROVINCE_BY_BPS_CODE = [
        '11' => 'Aceh',
        '12' => 'Sumatera Utara',
        '13' => 'Sumatera Barat',
        '14' => 'Riau',
        '15' => 'Jambi',
        '16' => 'Sumatera Selatan',
        '17' => 'Bengkulu',
        '18' => 'Lampung',
        '19' => 'Bangka Belitung',
        '21' => 'Kepulauan Riau',
        '31' => 'DKI Jakarta',
        '32' => 'Jawa Barat',
        '33' => 'Jawa Tengah',
        '34' => 'DI Yogyakarta',
        '35' => 'Jawa Timur',
        '36' => 'Banten',
        '51' => 'Bali',
        '52' => 'Nusa Tenggara Barat',
        '53' => 'Nusa Tenggara Timur',
        '61' => 'Kalimantan Barat',
        '62' => 'Kalimantan Tengah',
        '63' => 'Kalimantan Selatan',
        '64' => 'Kalimantan Timur',
        '65' => 'Kalimantan Utara',
        '71' => 'Sulawesi Utara',
        '72' => 'Sulawesi Tengah',
        '73' => 'Sulawesi Selatan',
        '74' => 'Sulawesi Tenggara',
        '75' => 'Gorontalo',
        '76' => 'Sulawesi Barat',
        '81' => 'Maluku',
        '82' => 'Maluku Utara',
        '91' => 'Papua Barat',
        '92' => 'Papua',
    ];

    /** Label verbatim ESDM → tier kecepatan (semakin besar = semakin cepat). */
    private const TYPE_CHARGE_TIER = [
        'Slow Charging' => 1,
        'Medium Charging' => 2,
        'Fast Charging' => 3,
        'Ultra Fast Charging' => 4,
    ];

    /** Label ESDM → label watt display (fallback; ESDM tidak menyediakan watt). */
    private const TYPE_CHARGE_WATT = [
        'Slow Charging' => '7 kW',
        'Medium Charging' => '22 kW',
        'Fast Charging' => '50 kW',
        'Ultra Fast Charging' => '150 kW',
    ];

    /**
     * Hydrate seluruh stasiun ESDM SPKLU ke charging_stations.
     *
     * Bila ada stasiun ESDM yang latitude/longitude-nya belum di-clean (masih
     * NULL padahal *_raw ada), otomatis jalankan geo cleaning dulu. Ini membuat
     * pipeline production-safe: urutan import → hydrate tetap menghasilkan
     * koordinat, tanpa harus ingat menjalankan esdm:clean-geo terpisah.
     *
     * Setelah charger box di-hydrate, fold status real-time per-box dari
     * connector_status yang sudah ada (bila poller pernah jalan). Charger box
     * yang konektornya belum terlacak tetap 'unknown' sampai poller berjalan.
     *
     * @return array{processed: int, created: int, updated: int, skipped: int, chargers: int, geo_cleaned: int, charger_boxes_folded: int}
     */
    public function hydrateFromEsdm(): array
    {
        // Auto-clean geo bila ada stasiun yang belum di-normalize (production safety).
        $geoCleaned = 0;
        $needsGeoClean = EsdmSinggatSpkluStation::whereNull('latitude')
            ->whereNotNull('latitude_spklu_raw')
            ->exists();
        if ($needsGeoClean) {
            $cleaning = app(EsdmSinggatGeoCleaningService::class);
            $result = $cleaning->clean(false);
            $geoCleaned = $result['spklu']['fixed'] ?? 0;
        }

        $stations = EsdmSinggatSpkluStation::with(['installations.connectors'])->get();

        $statusMap = EsdmSinggatStationStatus::query()
            ->get()
            ->keyBy('station_esdm_id');

        $guesser = new ScrapeDedupService;
        $plnProvider = Provider::where('name', 'PLN Mobile')->first();

        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'chargers' => 0];

        DB::connection('ev')->transaction(function () use ($stations, $statusMap, $guesser, $plnProvider, &$stats) {
            foreach ($stations as $station) {
                $stats['processed']++;

                $data = $this->buildStationData($station, $statusMap, $guesser, $plnProvider);
                if ($data === null) {
                    $stats['skipped']++;

                    continue;
                }

                $canonical = ChargingStation::updateOrCreate(
                    ['source' => self::SOURCE_ESDM, 'source_station_id' => $station->esdm_id],
                    $data
                );
                $stats[$canonical->wasRecentlyCreated ? 'created' : 'updated']++;

                $stats['chargers'] += $this->replaceChargers($canonical, $station);
            }
        });

        // Fold status real-time per-charger box dari connector_status yg sudah ada.
        // Backfill awal: semua charger box dapat status dari snapshot konektor terakhir.
        $chargerFolded = $this->foldAllChargerBoxesFromConnectorStatus();

        // Backfill status per-konektor (plug individual) dari snapshot terbaru.
        $connectorFolded = $this->foldAllConnectorsFromStatus();

        $stats['geo_cleaned'] = $geoCleaned;
        $stats['charger_boxes_folded'] = $chargerFolded;
        $stats['connectors_folded'] = $connectorFolded;

        return $stats;
    }

    /**
     * Fold status untuk SEMUA konektor (plug) canonical dari esdm_singgat_connector_status.
     * Backfill awal saat hydrate. Poller berikutnya hanya fold delta.
     */
    private function foldAllConnectorsFromStatus(): int
    {
        $rows = DB::connection('ev')->table('esdm_singgat_connector_status')
            ->select(['connector_esdm_id', 'status_konektor', 'status', 'last_seen_at'])
            ->get();

        $folded = 0;
        foreach ($rows as $row) {
            ChargingStationConnector::query()
                ->where('source_connector_id', $row->connector_esdm_id)
                ->update([
                    'status_konektor' => $row->status_konektor,
                    'status' => $row->status,
                    'status_updated_at' => $row->last_seen_at,
                ]);
            $folded++;
        }

        return $folded;
    }

    /**
     * Fold status untuk SEMUA charger box canonical dari connector_status.
     * Dipanggil hydrate (bukan hanya poll delta) supaya initial backfill lengkap.
     */
    private function foldAllChargerBoxesFromConnectorStatus(): int
    {
        // Ambil semua installation_esdm_id unik dari connector_status
        $instIds = DB::connection('ev')->table('esdm_singgat_connector_status')
            ->whereNotNull('installation_esdm_id')
            ->distinct()
            ->pluck('installation_esdm_id')
            ->all();

        if (empty($instIds)) {
            return 0;
        }

        $instData = array_map(fn ($id) => ['installation_esdm_id' => $id], $instIds);

        return $this->foldEsdmChargerStatuses($instData);
    }

    /**
     * Fold status real-time agregat ESDM ke canonical (dipanggil poller setelah
     * meng-update esdm_singgat_station_status). Update no-op bila stasiun belum
     * di-hydrate.
     *
     * @param  array{availability_level: string, available_count: int, charging_count: int, finishing_count: int, aggregated_at?: Carbon|null}  $aggregate
     */
    public function foldEsdmStatus(int $stationEsdmId, array $aggregate): void
    {
        ChargingStation::query()
            ->where('source', self::SOURCE_ESDM)
            ->where('source_station_id', $stationEsdmId)
            ->update([
                'availability_level' => $aggregate['availability_level'],
                'available_count' => $aggregate['available_count'],
                'charging_count' => $aggregate['charging_count'],
                'finishing_count' => $aggregate['finishing_count'],
                'status_updated_at' => $aggregate['aggregated_at'] ?? now(),
            ]);
    }

    /**
     * Fold status real-time per charger box (instalasi) ESDM ke canonical.
     *
     * Dipanggil poller setelah update konektor status. Untuk setiap charger box
     * canonical yang punya source_charger_id (ESDM instalasi id), hitung agregat
     * dari konektor-konektornya: berapa available/charging/finishing, lalu
     * turunkan availability_level. No-op bila charger box belum di-hydrate.
     *
     * @param  array<int, array{installation_esdm_id: int, aggregated_at?: Carbon|null}>  $installations  list of installation esdm IDs touched by this poll
     */
    public function foldEsdmChargerStatuses(array $installations, $aggregatedAt = null): int
    {
        $ids = array_column($installations, 'installation_esdm_id');
        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return 0;
        }

        // Agregat status per instalasi dari esdm_singgat_connector_status.
        // Join via installation_esdm_id di connector_status.
        $rows = DB::connection('ev')->table('esdm_singgat_connector_status')
            ->selectRaw('
                installation_esdm_id,
                count(*) as total,
                sum(status_konektor = "available") as avail,
                sum(status_konektor = "charging") as charg,
                sum(status_konektor = "finishing") as finish
            ')
            ->whereIn('installation_esdm_id', $ids)
            ->groupBy('installation_esdm_id')
            ->get();

        $folded = 0;
        $ts = $aggregatedAt ?? now();
        foreach ($rows as $row) {
            $avail = (int) $row->avail;
            $charg = (int) $row->charg;
            $finish = (int) $row->finish;
            $total = (int) $row->total;
            $level = $this->computeAvailabilityLevel($avail, $finish, $charg, $total);

            ChargingStationCharger::query()
                ->where('source_charger_id', $row->installation_esdm_id)
                ->update([
                    'availability_level' => $level,
                    'available_count' => $avail,
                    'charging_count' => $charg,
                    'finishing_count' => $finish,
                    'status_updated_at' => $ts,
                ]);
            $folded++;
        }

        return $folded;
    }

    /**
     * availability_level dari count. Dipakai untuk station & charger box.
     *
     * Logika sederhana: ada slot bebas → hijau (available). Tidak ada → merah
     * (occupied), apapun status konektornya (charging/finishing). finishing_count
     * tetap disimpan utk info detail "segera bebas" tapi tidak mengubah warna.
     */
    private function computeAvailabilityLevel(int $avail, int $finish, int $charg, int $total): string
    {
        if ($total === 0) {
            return 'offline';
        }
        if ($avail > 0) {
            return 'available';
        }

        // Tidak ada slot bebas. Bedakan occupied (semua sibuk) vs offline (mati).
        $activeCount = $avail + $finish + $charg;
        if ($activeCount > 0) {
            return 'occupied';
        }

        return 'offline';
    }

    // ─── Roll-up master data ────────────────────────────────────────────────

    /**
     * Bangun data kanonik untuk satu stasiun ESDM. Return null bila stasiun
     * tidak layak di-serving (mis. tanpa instalasi / tanpa nama).
     */
    private function buildStationData(
        EsdmSinggatSpkluStation $station,
        $statusMap,
        ScrapeDedupService $guesser,
        ?Provider $plnProvider
    ): ?array {
        $namaLokasi = trim((string) $station->nama_stasiun);
        if ($namaLokasi === '') {
            return null;
        }

        $installations = $station->installations;
        $primaryType = $this->resolvePrimaryTypeCharge($installations->pluck('jenis_pengisian_spklu')->all());
        $totalKonektor = (int) $installations->reduce(
            fn ($carry, $inst) => $carry + (int) $inst->connectors->count(),
            0
        );
        if ($totalKonektor === 0) {
            $totalKonektor = (int) $station->count_konektor;
        }

        [$providerId, $providerName] = $this->resolveProvider(
            $station->nama_badan_usaha, $guesser, $plnProvider, $namaLokasi
        );

        $raw = $station->raw_payload;
        $status = $statusMap[$station->esdm_id] ?? null;

        return [
            'nama_lokasi' => $namaLokasi,
            'alamat' => $station->alamat_spklu,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'kode_provinsi' => $station->kode_provinsi,
            'provinsi' => self::PROVINCE_BY_BPS_CODE[(string) $station->kode_provinsi] ?? null,
            'kabupaten_kota' => null, // ESDM hanya menyediakan kode_kota, bukan nama
            'type_charge' => $primaryType,
            'watt' => $primaryType !== null ? (self::TYPE_CHARGE_WATT[$primaryType] ?? null) : null,
            'total_charger' => $installations->count(),
            'total_konektor' => $totalKonektor,
            'nama_badan_usaha' => $station->nama_badan_usaha,
            'provider_id' => $providerId,
            'provider_name' => $providerName,
            'harga_pengisian' => $this->joinDistinctRaw($installations->pluck('harga_pengisian_raw')->all()),
            'harga_layanan' => $this->joinDistinctRaw($installations->pluck('harga_layanan_raw')->all()),
            'estimasi' => $station->estimasi,
            'estimasi_menit' => $station->estimasi_menit,
            'jarak' => isset($raw['jarak']) ? (float) $raw['jarak'] : null,
            'availability_level' => $status?->availability_level ?? 'unknown',
            'available_count' => $status?->available_count ?? 0,
            'charging_count' => $status?->charging_count ?? 0,
            'finishing_count' => $status?->finishing_count ?? 0,
            'status_updated_at' => $status?->aggregated_at,
            'raw_payload' => $raw,
        ];
    }

    /** Replace seluruh child charger + konektor milik satu stasiun. */
    private function replaceChargers(ChargingStation $canonical, EsdmSinggatSpkluStation $station): int
    {
        $canonical->chargers()->delete();

        $inserted = 0;
        foreach ($station->installations as $inst) {
            $connectors = $inst->connectors;
            $firstConnector = $connectors->first();

            $charger = ChargingStationCharger::create([
                'station_id' => $canonical->id,
                'source_charger_id' => $inst->esdm_id,
                'chargerbox_id' => $inst->nomor_identitas,
                'type_charge' => $inst->jenis_pengisian_spklu,
                'nama' => $inst->merek_mesin,
                'watt' => isset(self::TYPE_CHARGE_WATT[$inst->jenis_pengisian_spklu])
                    ? self::TYPE_CHARGE_WATT[$inst->jenis_pengisian_spklu]
                    : null,
                'jumlah_charger' => 1,
                'jumlah_konektor' => $connectors->count(),
                'icon' => null,
                'gambar' => $firstConnector?->img_path,
                'harga_pengisian' => $inst->harga_pengisian_raw,
                'harga_layanan' => $inst->harga_layanan_raw,
            ]);

            // Child connectors (plug individual) — master data + img_path.
            // Status real-time di-fold terpisah oleh poller.
            foreach ($connectors as $kon) {
                ChargingStationConnector::create([
                    'charger_id' => $charger->id,
                    'source_connector_id' => $kon->esdm_id,
                    'nama_konektor' => $kon->nama_konektor,
                    'img_path' => $kon->img_path,
                ]);
            }
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Pilih type_charge "primary" untuk stasiun: tier tertinggi yang tersedia
     * lintas instalasi (agar filter kecepatan & pin map tetap relevan).
     */
    private function resolvePrimaryTypeCharge(array $types): ?string
    {
        $best = null;
        $bestTier = 0;
        foreach (array_unique(array_filter($types)) as $type) {
            $tier = self::TYPE_CHARGE_TIER[$type] ?? 0;
            if ($tier > $bestTier) {
                $bestTier = $tier;
                $best = $type;
            }
        }

        return $best;
    }

    /**
     * Resolve provider: guess via ScrapeDedupService dengan cek GABUNGAN
     * nama_badan_usaha + nama_lokasi. Banyak stasiun ESDM punya nama legal
     * panjang di badan_usaha tapi nama singkat provider di nama_lokasi
     * (mis. badan="...", nama_lokasi="Astra Otopower" → provider "Astra").
     *
     * Strategi: guess dgn badan_usaha; bila no-match, coba guess dgn
     * gabungan "badan_usaha + nama_lokasi" utk catch provider di nama_lokasi.
     * Alias khusus ESDM: nama legal PLN tidak memuat token "PLN".
     *
     * @return array{0: string|null, 1: string|null} [provider_id, provider_name]
     */
    private function resolveProvider(?string $namaBadanUsaha, ScrapeDedupService $guesser, ?Provider $plnProvider, ?string $namaLokasi = null): array
    {
        $name = trim((string) $namaBadanUsaha);
        if ($name === '') {
            return [null, null];
        }

        if ($plnProvider !== null && (
            str_contains(strtoupper($name), 'LISTRIK NEGARA')
            || str_contains(strtoupper($name), ' PLN')
            || str_starts_with(strtoupper($name), 'PLN')
        )) {
            return [$plnProvider->id, $plnProvider->name];
        }

        // Coba badan_usaha dulu
        $provider = $guesser->guessProvider($name);
        if ($provider !== null) {
            return [$provider->id, $provider->name];
        }

        // No-match di badan_usaha → cek nama_lokasi secara terpisah.
        // Penting utk provider generik (Astra, Mall, Hotel) yg match-nya
        // starts-with: nama_lokasi "Astra Otopower" match "Astra", tapi
        // gabungan "ARDENDI JAYA SENTOSA Astra Otopower" tidak (tidak mulai dgn Astra).
        if ($namaLokasi !== null && trim($namaLokasi) !== '') {
            $provider = $guesser->guessProvider(trim($namaLokasi));
            if ($provider !== null) {
                return [$provider->id, $provider->name];
            }
            // Terakhir: coba gabungan utk non-generik (token match di mana saja).
            $combined = $name.' '.$namaLokasi;
            $provider = $guesser->guessProvider($combined);
            if ($provider !== null) {
                return [$provider->id, $provider->name];
            }
        }

        return [null, null];
    }

    /** Gabungkan nilai string unik (non-null/non-kosong) dengan " / ". */
    private function joinDistinctRaw(array $values): ?string
    {
        $clean = array_values(array_unique(array_filter(
            $values,
            fn ($v) => $v !== null && trim((string) $v) !== ''
        )));
        if ($clean === []) {
            return null;
        }

        return implode(' / ', $clean);
    }
}
