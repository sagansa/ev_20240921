<?php

namespace App\Services;

use App\Models\EsdmSinggatConnectorStatus;
use App\Models\EsdmSinggatConnectorStatusLog;
use App\Models\ChargingStationConnector;
use App\Models\EsdmSinggatSpkluConnector;
use App\Models\EsdmSinggatSpkluStation;
use App\Models\EsdmSinggatStationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Poller status real-time ESDM Singgat.
 *
 * Alur:
 *   1. Fetch POST get-lokasi (return-all 5.6MB compressed)
 *   2. Iterasi setiap konektor (level paling granular tempat status berada)
 *   3. Bandingkan dgn snapshot terakhir (esdm_singgat_connector_status)
 *   4. Bila status_konektor berubah → catat 1 baris di status_log + update snapshot
 *   5. Bila sama → hanya update last_seen_at
 *   6. Agregasi count per stasiun → upsert esdm_singgat_station_status
 *
 * Sesuai keputusan desain:
 *   - status_konektor unavailable & null TIDAK ditrack perubahan (cukup saat import
 *     JSON master), tapi tetap dihitung di agregat stasiun.
 *   - Perubahan yang ditrack: available ↔ charging ↔ finishing (siklus pengisian).
 *     unavailable/null → X tetap dicatat (bisa terjadi saat konektor baru muncul).
 *
 * Payload gambar base64 SENGAJA tidak disimpan — diabaikan sepenuhnya.
 */
class EsdmSinggatStatusPollerService
{
    private const API_URL = 'https://gatrik.esdm.go.id/singgat/api/api/get-lokasi';

    /** Status yang relevan untuk siklus charging real-time. */
    private const LIVE_STATUSES = ['available', 'charging', 'finishing'];

    public function __construct(
        private CanonicalStationHydrateService $canonicalHydrate,
    ) {}

    /**
     * @return array{batch: string, fetched_at: string, ...}
     */
    public function poll(?callable $progress = null): array
    {
        $batch = Str::uuid()->toString();
        $fetchedAt = now();

        // 1. Fetch dari ESDM
        $fetchStart = microtime(true);
        $data = $this->fetchFromEsdm();
        $fetchDuration = round(microtime(true) - $fetchStart, 2);

        $stations = $data['response']['spklu'] ?? [];
        if ($progress) {
            $progress('fetch', count($stations));
        }

        // 2. Build index konektor dari master lokal (utk resolve connector_id)
        $connectorIdMap = $this->loadConnectorIdMap();

        // 3. Build index snapshot terkini dari DB.
        //    Key: (connector_esdm_id, station_esdm_id) karena connector_esdm_id
        //    bisa berulang lintas stasiun di data ESDM.
        $currentSnapshots = EsdmSinggatConnectorStatus::query()
            ->get()
            ->keyBy(fn ($s) => $s->connector_esdm_id.'|'.($s->station_esdm_id ?? ''));

        // 4. Iterasi semua konektor dari poll baru
        $stats = $this->processConnectors(
            $stations,
            $currentSnapshots,
            $connectorIdMap,
            $batch,
            $fetchedAt,
            $progress
        );

        // 5. Agregasi per stasiun + fold charger box status
        $stationStats = $this->aggregateStations($fetchedAt, $stats['touched_installations']);

        return [
            'batch' => $batch,
            'fetched_at' => $fetchedAt->toDateTimeString(),
            'fetch_duration_s' => $fetchDuration,
            'stations_processed' => $stats['stations_processed'],
            'connectors_seen' => $stats['connectors_seen'],
            'status_changed' => $stats['status_changed'],
            'new_connectors' => $stats['new_connectors'],
            'logs_inserted' => $stats['logs_inserted'],
            'stations_aggregated' => $stationStats['aggregated'],
            'canonical_folded' => $stationStats['folded_to_canonical'],
            'charger_boxes_folded' => $stationStats['charger_boxes_folded'],
        ];
    }

    // ─── Fetch ──────────────────────────────────────────────────────────────

    private function fetchFromEsdm(): array
    {
        // Response ESDM ~5.6MB, waktu ~35-50s. Default timeout 30s kurang.
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip',
            'Origin' => 'https://gatrik.esdm.go.id',
            'Referer' => 'https://gatrik.esdm.go.id/singgat/',
        ])->timeout(120)->post(self::API_URL, []);

        if (! $response->successful()) {
            throw new \RuntimeException("ESDM fetch gagal: {$response->status()} {$response->reason()}");
        }

        $data = $response->json();
        if (! isset($data['response']['spklu'])) {
            throw new \RuntimeException('Format respons ESDM tidak valid: key response.spklu tidak ada.');
        }

        return $data;
    }

    // ─── Process connectors ─────────────────────────────────────────────────

    private function processConnectors(
        array $stations,
        $currentSnapshots,
        array $connectorIdMap,
        string $batch,
        $fetchedAt,
        ?callable $progress
    ): array {
        $seen = 0;
        $changed = 0;
        $newConnectors = 0;
        $logsToInsert = [];
        $now = now();
        $processedKeys = []; // dedup intra-poll: data ESDM punya duplikat identik
        $touchedInstallations = []; // instalasi yg konektornya berubah → utk fold charger box

        foreach ($stations as $i => $stasiun) {
            $stationEsmId = $stasiun['id'] ?? null;
            if ($stationEsmId === null) {
                continue;
            }

            foreach ($stasiun['instalasi'] ?? [] as $inst) {
                $instId = $inst['id'] ?? null;
                foreach ($inst['konektor'] ?? [] as $kon) {
                    $konId = $kon['id'] ?? null;
                    if ($konId === null) {
                        continue;
                    }

                    // Dedup: skip record identik yg sudah diproses di poll ini.
                    $dedupKey = $konId.'|'.($stationEsmId ?? '').'|'.($instId ?? '');
                    if (isset($processedKeys[$dedupKey])) {
                        continue;
                    }
                    $processedKeys[$dedupKey] = true;

                    $seen++;

                    $newStatusKonektor = $kon['status_konektor'] ?? null;
                    $newStatus = $kon['status'] ?? null;
                    $localConnectorId = $connectorIdMap[$konId] ?? null;

                    $snapshot = $currentSnapshots->get($konId.'|'.($stationEsmId ?? ''));

                    if ($snapshot === null) {
                        // Konektor baru (belum pernah terlihat)
                        $newConnectors++;
                        $logsToInsert[] = $this->buildLogRow(
                            $konId, $localConnectorId, $stationEsmId, $instId,
                            null, $newStatus, null, $newStatusKonektor,
                            $batch, $fetchedAt, $now
                        );

                        EsdmSinggatConnectorStatus::create([
                            'connector_esdm_id' => $konId,
                            'connector_id' => $localConnectorId,
                            'station_esdm_id' => $stationEsmId,
                            'installation_esdm_id' => $instId,
                            'status' => $newStatus,
                            'status_konektor' => $newStatusKonektor,
                            'status_since' => $fetchedAt,
                            'last_seen_at' => $fetchedAt,
                        ]);
                        $changed++;
                        if ($instId !== null) {
                            $touchedInstallations[$instId] = true;
                        }
                    } else {
                        // Konektor sudah ada — cek perubahan
                        $oldStatusKonektor = $snapshot->status_konektor;
                        $statusChanged = ($oldStatusKonektor !== $newStatusKonektor);

                        $updateData = [
                            'last_seen_at' => $fetchedAt,
                            'installation_esdm_id' => $instId, // pastikan selalu terisi
                        ];

                        if ($statusChanged) {
                            $logsToInsert[] = $this->buildLogRow(
                                $konId, $localConnectorId, $stationEsmId, $instId,
                                $snapshot->status, $newStatus,
                                $oldStatusKonektor, $newStatusKonektor,
                                $batch, $fetchedAt, $now
                            );
                            $updateData['status'] = $newStatus;
                            $updateData['status_konektor'] = $newStatusKonektor;
                            $updateData['status_since'] = $fetchedAt;
                            $changed++;
                            if ($instId !== null) {
                                $touchedInstallations[$instId] = true;
                            }
                        }

                        $snapshot->update($updateData);
                    }
                }
            }

            if ($progress && $i > 0 && $i % 500 === 0) {
                $progress('process', $i);
            }
        }

        // Bulk insert log (lebih efisien dari insert per-row)
        if (! empty($logsToInsert)) {
            foreach (array_chunk($logsToInsert, 500) as $chunk) {
                EsdmSinggatConnectorStatusLog::insert($chunk);
            }
        }

        // Fold status per-konektor ke canonical (plug individual).
        // Update charging_station_connectors dari snapshot terbaru.
        $connectorsFolded = 0;
        if ($changed > 0) {
            $touchedConnectorIds = EsdmSinggatConnectorStatus::query()
                ->where('last_seen_at', $fetchedAt)
                ->pluck('status_konektor', 'connector_esdm_id');
            foreach ($touchedConnectorIds as $connectorEsdmId => $statusKonektor) {
                ChargingStationConnector::query()
                    ->where('source_connector_id', $connectorEsdmId)
                    ->update([
                        'status_konektor' => $statusKonektor,
                        'status_updated_at' => $fetchedAt,
                    ]);
                $connectorsFolded++;
            }
        }

        return [
            'stations_processed' => count($stations),
            'connectors_seen' => $seen,
            'status_changed' => $changed,
            'new_connectors' => $newConnectors,
            'logs_inserted' => count($logsToInsert),
            'touched_installations' => array_keys($touchedInstallations),
            'connectors_folded' => $connectorsFolded,
        ];
    }

    private function buildLogRow(
        int $konId, ?int $localId, ?int $stationEsmId, ?int $instEsmId,
        ?string $fromStatus, ?string $toStatus,
        ?string $fromStatusKon, ?string $toStatusKon,
        string $batch, $fetchedAt, $now
    ): array {
        return [
            'connector_esdm_id' => $konId,
            'connector_id' => $localId,
            'station_esdm_id' => $stationEsmId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_status_konektor' => $fromStatusKon,
            'to_status_konektor' => $toStatusKon,
            'observed_at' => $fetchedAt,
            'poll_batch' => $batch,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // ─── Aggregate stations ─────────────────────────────────────────────────

    private function aggregateStations($fetchedAt, array $touchedInstallations = []): array
    {
        // Group konektor by station, hitung per status
        $rows = DB::connection('ev')->table('esdm_singgat_connector_status')
            ->selectRaw('
                station_esdm_id,
                count(*) as total,
                sum(status_konektor = "available") as avail,
                sum(status_konektor = "charging") as charg,
                sum(status_konektor = "finishing") as finish,
                sum(status_konektor = "unavailable") as unavail,
                sum(status_konektor IS NULL) as unknown
            ')
            ->whereNotNull('station_esdm_id')
            ->groupBy('station_esdm_id')
            ->get();

        $aggregated = 0;
        $stationIdMap = $this->loadStationIdMap();
        $now = now();

        foreach ($rows as $row) {
            $avail = (int) $row->avail;
            $charg = (int) $row->charg;
            $finish = (int) $row->finish;
            $total = (int) $row->total;

            $level = $this->computeAvailabilityLevel($avail, $finish, $charg, $total);

            EsdmSinggatStationStatus::updateOrCreate(
                ['station_esdm_id' => $row->station_esdm_id],
                [
                    'station_id' => $stationIdMap[$row->station_esdm_id] ?? null,
                    'total_connectors' => $total,
                    'available_count' => $avail,
                    'charging_count' => $charg,
                    'finishing_count' => $finish,
                    'unavailable_count' => (int) $row->unavail,
                    'unknown_count' => (int) $row->unknown,
                    'availability_level' => $level,
                    'aggregated_at' => $fetchedAt,
                ]
            );

            // Fold agregat ke tabel kanonik agar serving tidak perlu JOIN.
            $this->canonicalHydrate->foldEsdmStatus((int) $row->station_esdm_id, [
                'availability_level' => $level,
                'available_count' => $avail,
                'charging_count' => $charg,
                'finishing_count' => $finish,
                'aggregated_at' => $fetchedAt,
            ]);
            $aggregated++;
        }

        // Fold status per-charger box (hanya instalasi yg konektornya berubah).
        $chargerBoxesFolded = 0;
        if (! empty($touchedInstallations)) {
            $instData = array_map(
                fn ($id) => ['installation_esdm_id' => $id],
                $touchedInstallations
            );
            $chargerBoxesFolded = $this->canonicalHydrate->foldEsdmChargerStatuses($instData, $fetchedAt);
        }

        return [
            'aggregated' => $aggregated,
            'folded_to_canonical' => $aggregated,
            'charger_boxes_folded' => $chargerBoxesFolded,
        ];
    }

    /**
     * available: ada slot bebas
     * occupied:  tidak ada slot bebas (charging/finishing) — 0 slot = merah
     * offline:   semua unavailable/null (tidak aktif real-time)
     *
     * Catatan: finishing tidak mengubah warna jadi partial. 0 slot bebas = occupied,
     * apapun status konektornya. finishing_count tetap utk info detail.
     */
    private function computeAvailabilityLevel(int $avail, int $finish, int $charg, int $total): string
    {
        if ($total === 0) {
            return 'offline';
        }
        if ($avail > 0) {
            return 'available';
        }

        $activeCount = $avail + $finish + $charg;
        if ($activeCount > 0) {
            return 'occupied';
        }

        return 'offline';
    }

    // ─── Helpers: load FK maps ──────────────────────────────────────────────

    /** Map: connector_esdm_id → local connector_id (esdm_singgat_spklu_connectors.id). */
    private function loadConnectorIdMap(): array
    {
        return EsdmSinggatSpkluConnector::query()
            ->whereNotNull('esdm_id')
            ->pluck('id', 'esdm_id')
            ->all();
    }

    /** Map: station_esdm_id → local station_id (esdm_singgat_spklu_stations.id). */
    private function loadStationIdMap(): array
    {
        return EsdmSinggatSpkluStation::query()
            ->whereNotNull('esdm_id')
            ->pluck('id', 'esdm_id')
            ->all();
    }
}
