<?php

namespace App\Services;

use App\Models\ChargingStation;
use App\Models\PlnEsdmStationMatch;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Matching stasiun PLN ↔ ESDM — pipeline 2 stage, hasil berupa tabel link.
 *
 * Stage 1 (deterministik): pre-filter haversine 1 km + gate provinsi wajib sama
 *   + similar_text nama → auto-link high-confidence (approved, decided_by=system).
 * Stage 2 (AI): kandidat ambiguous (jarak ≤500 m & nama ≥60%) dikirim ke
 *   LmStudioClient. Confidence ≥80 & match=true → ai_suggested (perlu approve).
 *   Selain itu → rejected_ai. Bila LM Studio down/error → fallback `pending`
 *   utk review admin (tidak pernah crash).
 *
 * Produk akhir HANYA link table (pln_esdm_station_matches) — master ESDM/PLN
 * tidak disentuh. Status serving dipisah: applyStatusToCanonical() mem-fold
 * status dari stasiun ESDM yang approved ke stasiun PLN — dipanggil poller
 * `esdm:poll-status` (tiap 10 menit) & command `pln:match-esdm`.
 *
 * Hanya `approved` yang di-fold; PLN boleh punya banyak kandidat lain
 * (rejected/ai_suggested/pending), tapi 1 approved (constraint app layer).
 */
class PlnEsdmMatchService
{
    public const SOURCE_PLN = 'pln';

    public const SOURCE_ESDM = 'esdm';

    // Status match
    public const STATUS_PENDING = 'pending';

    public const STATUS_AI_SUGGESTED = 'ai_suggested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REJECTED_AI = 'rejected_ai';

    // Metode match
    public const METHOD_AUTO_GEO = 'auto_geo';

    public const METHOD_AUTO_GEO_NAME = 'auto_geo_name';

    public const METHOD_AI = 'ai';

    public const METHOD_MANUAL = 'manual';

    // Threshold klasifikasi
    public const RADIUS_KM = 1;            // pre-filter SQL haversine
    public const DIST_AUTO_M = 100;        // auto-link bila nama ≥85%
    public const SIM_AUTO_PCT = 85;
    public const DIST_AI_M = 500;          // di bawah ini = kandidat relevan
    public const SIM_AI_PCT = 60;          // ≥60% & jarak ≤500m → kirim ke AI
    public const AI_CONF_SUGGEST = 80;     // confidence AI utk ai_suggested

    private const CANDIDATE_LIMIT = 10;    // maks kandidat per stasiun PLN

    private ?bool $aiAvailable = null;     // cache hasil isAvailable() per run

    public function __construct(
        private LmStudioClient $ai,
        private GeoVerificationService $geo,
        private ScrapeDedupService $dedup,
    ) {}

    /**
     * Jalankan pipeline matching utk semua stasiun PLN.
     *
     * @param  int|null  $aiLimit  Batas jumlah panggilan AI (stage 2). Null = tanpa batas.
     *                              Panggilan ke-n setelah limit akan fallback ke `pending`
     *                              (review admin) — memungkinkan run AI inkremental.
     * @param  callable(string $message, int $done, int $total): void|null  $progress
     * @return array{processed: int, auto_linked: int, ai_suggested: int, ai_rejected: int, pending_review: int, ai_errors: int, fallbacks: int, skipped_preserved: int, ai_capped: int}
     */
    public function match(bool $force = false, bool $dryRun = false, ?callable $progress = null, ?int $aiLimit = null): array
    {
        $summary = [
            'processed' => 0,
            'auto_linked' => 0,
            'ai_suggested' => 0,
            'ai_rejected' => 0,
            'pending_review' => 0,
            'ai_errors' => 0,
            'fallbacks' => 0,
            'skipped_preserved' => 0,
            'ai_capped' => 0,
        ];

        $plnStations = ChargingStation::query()
            ->where('source', self::SOURCE_PLN)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->get();

        $total = $plnStations->count();
        $done = 0;
        $aiUsed = 0;

        foreach ($plnStations as $plnStation) {
            $done++;
            if ($progress !== null) {
                $progress("stasiun PLN {$done}/{$total} — {$plnStation->nama_lokasi}", $done, $total);
            }

            $candidates = $this->findCandidates($plnStation);
            if ($candidates->isEmpty()) {
                continue;
            }

            $summary['processed']++;

            foreach ($candidates as $candidate) {
                // Cap AI: kandidat setelah aiLimit → fallback pending (run inkremental).
                $result = $this->classify($plnStation, $candidate['esdm'], $candidate, $aiLimit, $aiUsed);
                if (($result['capped'] ?? false)) {
                    $summary['ai_capped']++;
                } elseif (($result['ai_error'] ?? false) === false && ($result['fallback'] ?? false) === false && $result['method'] === self::METHOD_AI) {
                    $aiUsed++;
                }
                $this->upsertMatch($plnStation, $candidate['esdm'], $result, $force, $dryRun, $summary);
            }
        }

        return $summary;
    }

    /**
     * Fold status real-time dari stasiun ESDM (approved) ke stasiun PLN.
     * Update no-op bila stasiun tidak ada. Return jumlah stasiun PLN di-update.
     */
    public function applyStatusToCanonical(): int
    {
        $matches = PlnEsdmStationMatch::query()
            ->where('match_status', self::STATUS_APPROVED)
            ->get();

        if ($matches->isEmpty()) {
            return 0;
        }

        $esdmStations = ChargingStation::query()
            ->where('source', self::SOURCE_ESDM)
            ->whereIn('id', $matches->pluck('esdm_station_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $plnStations = ChargingStation::query()
            ->where('source', self::SOURCE_PLN)
            ->whereIn('id', $matches->pluck('pln_station_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $folded = 0;
        foreach ($matches as $match) {
            $esdm = $esdmStations->get($match->esdm_station_id);
            $pln = $plnStations->get($match->pln_station_id);
            if ($esdm === null || $pln === null) {
                continue;
            }

            $pln->update([
                'availability_level' => $esdm->availability_level ?? 'unknown',
                'available_count' => $esdm->available_count ?? 0,
                'charging_count' => $esdm->charging_count ?? 0,
                'finishing_count' => $esdm->finishing_count ?? 0,
                'status_updated_at' => $esdm->status_updated_at ?? now(),
            ]);
            $folded++;
        }

        return $folded;
    }

    /**
     * Approve satu match (admin) sebagai pemenang utk PLN-nya. SEMUA kandidat
     * lain (approved/pending/ai_suggested) utk PLN yg sama auto-reject
     * ("Superseded") — tiap PLN hanya boleh punya 1 pemenang. Lalu re-fold
     * status canonical (status ESDM pemenang di-teruskan ke PLN).
     */
    public function approve(int $matchId, ?string $user): PlnEsdmStationMatch
    {
        $match = PlnEsdmStationMatch::findOrFail($matchId);

        // Demote SEMUA kandidat lain utk PLN yg sama (apa pun statusnya).
        // Kandidat yg sudah rejected/rejected_ai tetap (sudah final, tidak
        // perlu ditimpa reason-nya).
        PlnEsdmStationMatch::query()
            ->where('pln_station_id', $match->pln_station_id)
            ->where('id', '!=', $match->id)
            ->whereIn('match_status', [self::STATUS_APPROVED, self::STATUS_PENDING, self::STATUS_AI_SUGGESTED])
            ->update([
                'match_status' => self::STATUS_REJECTED,
                'rejected_reason' => 'Superseded — kandidat lain di-approve admin sebagai pemenang.',
                'decided_by' => $user,
                'decided_at' => now(),
            ]);

        $match->update([
            'match_status' => self::STATUS_APPROVED,
            'match_method' => $match->match_method === self::METHOD_AI ? self::METHOD_AI : self::METHOD_MANUAL,
            'decided_by' => $user,
            'decided_at' => now(),
            'rejected_reason' => null,
        ]);

        $this->applyStatusToCanonical();

        return $match->fresh();
    }

    /** Reject satu match (admin) — wajib reason. */
    public function reject(int $matchId, ?string $reason, ?string $user): PlnEsdmStationMatch
    {
        $match = PlnEsdmStationMatch::findOrFail($matchId);

        $match->update([
            'match_status' => self::STATUS_REJECTED,
            'rejected_reason' => $reason,
            'decided_by' => $user,
            'decided_at' => now(),
        ]);

        return $match->fresh();
    }

    /**
     * Cleanup retroaktif: utk setiap PLN yang sudah punya pemenang (approved),
     * demote kandidat lain yg masih pending/ai_suggested menjadi rejected
     * ("Superseded"). Dipakai utk konsistensi data lama setelah perubahan
     * approve() — idempoten.
     *
     * @return int Jumlah kandidat yg di-demote.
     */
    public function cleanupSupersededCandidates(): int
    {
        $plnIdsWithWinner = PlnEsdmStationMatch::query()
            ->where('match_status', self::STATUS_APPROVED)
            ->pluck('pln_station_id')
            ->unique();

        if ($plnIdsWithWinner->isEmpty()) {
            return 0;
        }

        return PlnEsdmStationMatch::query()
            ->whereIn('pln_station_id', $plnIdsWithWinner)
            ->whereIn('match_status', [self::STATUS_PENDING, self::STATUS_AI_SUGGESTED])
            ->update([
                'match_status' => self::STATUS_REJECTED,
                'rejected_reason' => 'Superseded (cleanup) — PLN ini sudah punya pemenang.',
                'decided_by' => 'system',
                'decided_at' => now(),
            ]);
    }

    // ─── Stage 1: kandidat deterministik ────────────────────────────────────

    /**
     * Kandidat stasiun ESDM dalam radius 1 km (haversine SQL), gate provinsi
     * wajib sama, lalu skor jarak presisi (distanceM) + kemiripan nama.
     * Hanya kandidat ≤ DIST_AI_M yang dipertahankan (selain itu dianggap
     * terlalu jauh walau masuk radius pre-filter).
     *
     * @return Collection<int, array{esdm: ChargingStation, distance_m: int, similarity_pct: int}>
     */
    private function findCandidates(ChargingStation $pln): Collection
    {
        $haversine = $this->haversineExpression((float) $pln->latitude, (float) $pln->longitude);

        $rows = ChargingStation::query()
            ->select('charging_stations.*')
            ->selectRaw($haversine.' AS distance')
            ->where('source', self::SOURCE_ESDM)
            ->whereRaw($haversine.' <= '.self::RADIUS_KM)
            ->orderBy('distance')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $candidates = [];
        foreach ($rows as $esdm) {
            if (! $this->provinceMatches($pln->provinsi, $esdm->provinsi)) {
                continue;
            }

            $distanceM = $this->geo->distanceM(
                (float) $pln->latitude,
                (float) $pln->longitude,
                (float) $esdm->latitude,
                (float) $esdm->longitude,
            );

            if ($distanceM > self::DIST_AI_M) {
                continue;
            }

            similar_text(
                $this->normalizeMatchName($pln->nama_lokasi),
                $this->normalizeMatchName($esdm->nama_lokasi),
                $percent
            );

            $candidates[] = [
                'esdm' => $esdm,
                'distance_m' => $distanceM,
                'similarity_pct' => (int) round($percent),
            ];
        }

        // Terdekat dulu, lalu nama paling mirip.
        usort($candidates, fn ($a, $b) => $a['distance_m'] <=> $b['distance_m']
            ?: $b['similarity_pct'] <=> $a['similarity_pct']);

        return collect($candidates);
    }

    /** Gate provinsi: wajib sama (dinormalisasi). Nilai kosong = tidak bisa dibandingkan → lolos. */
    private function provinceMatches(?string $a, ?string $b): bool
    {
        $a = trim((string) $a);
        $b = trim((string) $b);
        if ($a === '' || $b === '') {
            return true;
        }

        $na = $this->dedup->normalizeName($a);
        $nb = $this->dedup->normalizeName($b);
        if ($na === $nb) {
            return true;
        }

        // Varian "DI Yogyakarta" vs "Yogyakarta", "DAERAH ISTIMEWA YOGYAKARTA" dsb.
        // Word-boundary agar tidak menghapus substring "DI" di tengah nama lain.
        $strip = fn (string $s): string => preg_replace(
            ['/\bDAERAHISTIMEWA\b/', '/^DI\b\s*/'],
            '',
            $s
        ) ?? $s;

        return $strip($na) === $strip($nb);
    }

    // ─── Klasifikasi (stage 1 + 2) ──────────────────────────────────────────

    /**
     * @param  int|null  $aiLimit  Batas panggilan AI (null = tanpa batas).
     * @param  int  $aiUsed  Jumlah panggilan AI yg sudah dipakai (pass-by-value, cap check).
     * @return array{status: string, method: string, similarity_pct: int, distance_m: int, ai_confidence: float|null, ai_reasoning: array|null, ai_error: bool, fallback: bool, capped: bool}
     */
    private function classify(ChargingStation $pln, ChargingStation $esdm, array $candidate, ?int $aiLimit = null, int $aiUsed = 0): array
    {
        $distanceM = $candidate['distance_m'];
        $sim = $candidate['similarity_pct'];

        // Stage 1 — geo+nama kuat → auto-link.
        if ($distanceM <= self::DIST_AUTO_M && $sim >= self::SIM_AUTO_PCT) {
            return $this->result(self::STATUS_APPROVED, self::METHOD_AUTO_GEO_NAME, $sim, $distanceM, null, null);
        }

        // Stage 2 — ambiguous (jarak ≤500m, nama ≥60%) → kirim ke AI.
        if ($distanceM <= self::DIST_AI_M && $sim >= self::SIM_AI_PCT) {
            // Cap: bila aiLimit tercapai → fallback pending (run inkremental), bukan error.
            if ($aiLimit !== null && $aiUsed >= $aiLimit) {
                return $this->result(
                    self::STATUS_PENDING,
                    self::METHOD_AI,
                    $sim,
                    $distanceM,
                    null,
                    ['note' => 'AI capped (ai-limit tercapai) — defer ke run berikutnya.'],
                    capped: true,
                );
            }

            return $this->classifyWithAi($pln, $esdm, $candidate);
        }

        // Geo dekat tapi nama sangat beda → pending review admin.
        return $this->result(
            self::STATUS_PENDING,
            self::METHOD_AUTO_GEO,
            $sim,
            $distanceM,
            null,
            ['note' => 'Geo dekat namun nama sangat berbeda — perlu review manual.']
        );
    }

    private function classifyWithAi(ChargingStation $pln, ChargingStation $esdm, array $candidate): array
    {
        if ($this->aiAvailable === null) {
            $this->aiAvailable = $this->ai->isAvailable();
        }

        // Fallback decision: AI mati/disabled → pending (tanpa mencoba request).
        if (! $this->aiAvailable) {
            return $this->result(
                self::STATUS_PENDING,
                self::METHOD_AI,
                $candidate['similarity_pct'],
                $candidate['distance_m'],
                null,
                ['note' => 'LM Studio tidak tersedia — fallback review manual.'],
                aiError: true,
                fallback: true,
            );
        }

        try {
            $aiResult = $this->ai->classifyMatch($this->buildAiPayload($pln, $esdm, $candidate));
            $reasoning = [
                'match' => $aiResult['match'],
                'confidence' => $aiResult['confidence'],
                'reason' => $aiResult['reason'],
                'signals' => $aiResult['signals'],
            ];

            if ($aiResult['match'] && $aiResult['confidence'] >= self::AI_CONF_SUGGEST) {
                return $this->result(
                    self::STATUS_AI_SUGGESTED,
                    self::METHOD_AI,
                    $candidate['similarity_pct'],
                    $candidate['distance_m'],
                    $aiResult['confidence'],
                    $reasoning,
                );
            }

            return $this->result(
                self::STATUS_REJECTED_AI,
                self::METHOD_AI,
                $candidate['similarity_pct'],
                $candidate['distance_m'],
                $aiResult['confidence'],
                $reasoning,
            );
        } catch (Throwable $e) {
            // AI error saat runtime → fallback pending (tidak crash).
            return $this->result(
                self::STATUS_PENDING,
                self::METHOD_AI,
                $candidate['similarity_pct'],
                $candidate['distance_m'],
                null,
                ['note' => 'LM Studio error — fallback review manual.', 'error' => $e->getMessage()],
                aiError: true,
                fallback: true,
            );
        }
    }

    private function buildAiPayload(ChargingStation $pln, ChargingStation $esdm, array $candidate): array
    {
        return [
            'pln_name' => $pln->nama_lokasi,
            'pln_addr' => $pln->alamat,
            'pln_lat' => $pln->latitude,
            'pln_lng' => $pln->longitude,
            'pln_province' => $pln->provinsi,
            'pln_provider' => $pln->provider_name,
            'esdm_name' => $esdm->nama_lokasi,
            'esdm_addr' => $esdm->alamat,
            'esdm_lat' => $esdm->latitude,
            'esdm_lng' => $esdm->longitude,
            'esdm_province' => $esdm->provinsi,
            'esdm_distance_m' => $candidate['distance_m'],
            'name_similarity_pct' => $candidate['similarity_pct'],
        ];
    }

    private function result(
        string $status,
        string $method,
        int $sim,
        int $distanceM,
        ?float $aiConfidence,
        ?array $aiReasoning,
        bool $aiError = false,
        bool $fallback = false,
        bool $capped = false,
    ): array {
        return [
            'status' => $status,
            'method' => $method,
            'similarity_pct' => $sim,
            'distance_m' => $distanceM,
            'ai_confidence' => $aiConfidence,
            'ai_reasoning' => $aiReasoning,
            'ai_error' => $aiError,
            'fallback' => $fallback,
            'capped' => $capped,
        ];
    }

    /**
     * Normalisasi nama SPKLU utk perbandingan kemiripan — lebih agresif dari
     * ScrapeDedupService::normalizeName() agar pasangan dengan konvensi nama
     * berbeda (prefix operator, suffix watt) tetap cocok.
     *
     * Yang di-strip:
     *  - Prefix operator dalam kurung: "(BLUECHARGE) X" → "X"
     *  - Prefix kata generik: "SPKLU X" → "X"
     *  - Suffix watt + urutan: "X 22 kW (1)" → "X"
     * Lalu upper + alnum-only + collapse spaces (pola normalizeName).
     */
    private function normalizeMatchName(?string $name): string
    {
        $s = strtoupper(trim((string) $name));

        // Strip prefix dalam kurung di awal: "(BRAND) Rest" → "Rest".
        $s = preg_replace('/^\([^)]*\)\s*/', '', $s) ?? $s;

        // Strip prefix kata generik SPKLU / SPBU di awal.
        $s = preg_replace('/^\s*(SPKLU|SPBU)\b\s*/i', '', $s) ?? $s;

        // Strip suffix watt + opsi urutan: " 22 kW", " 7,4 kW (1)".
        $s = preg_replace('/\s+\d+(?:[.,]\d+)?\s*KW(?:\s*\(\d+\))?\s*$/i', '', $s) ?? $s;

        // Alnum-only + collapse spaces (sama dengan ScrapeDedupService::normalizeName).
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s) ?? '';
        $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');

        return $s;
    }

    // ─── Upsert & audit ─────────────────────────────────────────────────────

    /**
     * Upsert link by (pln_station_id, esdm_station_id). Idempoten: keputusan
     * final (approved/rejected) tidak di-overwrite saat re-run kecuali --force.
     * Auto-approve dilewati bila PLN sudah punya 1 approved lain (tanpa force).
     */
    private function upsertMatch(
        ChargingStation $pln,
        ChargingStation $esdm,
        array $result,
        bool $force,
        bool $dryRun,
        array &$summary,
    ): void {
        $existing = PlnEsdmStationMatch::query()
            ->where('pln_station_id', $pln->id)
            ->where('esdm_station_id', $esdm->id)
            ->first();

        if ($existing && ! $force && in_array($existing->match_status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            $summary['skipped_preserved']++;

            return;
        }

        // PLN hanya boleh punya 1 approved (app layer).
        if ($result['status'] === self::STATUS_APPROVED && ! $force) {
            $otherApproved = PlnEsdmStationMatch::query()
                ->where('pln_station_id', $pln->id)
                ->where('match_status', self::STATUS_APPROVED);
            if ($existing) {
                $otherApproved->where('id', '!=', $existing->id);
            }
            if ($otherApproved->exists()) {
                $result['status'] = self::STATUS_PENDING;
                $result['ai_reasoning'] = ['note' => 'Auto-link dilewati — stasiun PLN ini sudah punya match approved lain.'];
            }
        }

        $final = [
            'status' => $result['status'],
            'ai_error' => $result['ai_error'],
            'fallback' => $result['fallback'],
        ];

        if ($dryRun) {
            $this->bumpSummary($summary, $final);

            return;
        }

        $data = [
            'pln_source_station_id' => $pln->source_station_id !== null ? (string) $pln->source_station_id : null,
            'esdm_source_station_id' => $esdm->source_station_id !== null ? (string) $esdm->source_station_id : null,
            'pln_name' => $pln->nama_lokasi,
            'esdm_name' => $esdm->nama_lokasi,
            'match_status' => $result['status'],
            'match_method' => $result['method'],
            'similarity_pct' => $result['similarity_pct'],
            'distance_m' => $result['distance_m'],
            'ai_confidence' => $result['ai_confidence'],
            'ai_reasoning' => $result['ai_reasoning'],
            'rejected_reason' => null,
        ];

        // decided_by/at hanya utk keputusan final.
        $decided = $this->decidedBy($result['status'], $result['method']);
        $data['decided_by'] = $decided['by'];
        $data['decided_at'] = $decided['at'];

        $match = PlnEsdmStationMatch::updateOrCreate(
            ['pln_station_id' => $pln->id, 'esdm_station_id' => $esdm->id],
            $data
        );

        // --force auto-approve → demote approved lain utk PLN yg sama.
        if ($force && $result['status'] === self::STATUS_APPROVED) {
            $others = PlnEsdmStationMatch::query()
                ->where('pln_station_id', $pln->id)
                ->where('id', '!=', $match->id)
                ->where('match_status', self::STATUS_APPROVED);
            $others->update([
                'match_status' => self::STATUS_REJECTED,
                'rejected_reason' => 'Digantikan hasil re-run --force.',
                'decided_by' => 'system',
                'decided_at' => now(),
            ]);
        }

        $this->bumpSummary($summary, $final);
    }

    /** decided_by / decided_at utk keputusan final. */
    private function decidedBy(string $status, string $method): array
    {
        if ($status === self::STATUS_APPROVED) {
            return ['by' => 'system', 'at' => now()];
        }
        if ($status === self::STATUS_REJECTED_AI) {
            return ['by' => 'ai', 'at' => now()];
        }

        return ['by' => null, 'at' => null];
    }

    private function bumpSummary(array &$summary, array $result): void
    {
        $summary[$this->summaryKey($result['status'])]++;
        if ($result['ai_error']) {
            $summary['ai_errors']++;
        }
        if ($result['fallback']) {
            $summary['fallbacks']++;
        }
    }

    private function summaryKey(string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'auto_linked',
            self::STATUS_AI_SUGGESTED => 'ai_suggested',
            self::STATUS_REJECTED_AI => 'ai_rejected',
            default => 'pending_review',
        };
    }

    /** Haversine (km) SQL expression — pola ScrapeDedupService. */
    private function haversineExpression(float $lat, float $lng): string
    {
        return "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";
    }
}
