<?php

namespace App\Services;

use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;

class ScrapeDedupService
{
    private array $providerPatterns = [
        '/\bPLN\b/i' => 'PLN Mobile',
        '/\bPERTAMINA\b/i' => 'Pertamina',
        '/\bSHELL\b/i' => 'Shell',
        '/\bCHARGE\s?\+?\b|\bCHARGEPLUS\b|\bCHARGE PLUS\b/i' => 'Charge+',
        '/\bVOLTRON\b/i' => 'Voltron',
        '/\bWULING\b/i' => 'Wuling',
        '/\bHYUNDAI\b/i' => 'Hyundai',
        '/\bSTARVO\b/i' => 'Starvo',
        '/\bCASINO\b/i' => 'Casino',
        '/\bDAYAGREEN\b/i' => 'Dayagreen',
        '/\bSTROOM\b/i' => 'Stroom',
        '/\bTOYOTA\b|\bLEXUS\b/i' => 'Toyota Lexus',
    ];

    public function guessProvider(string $name): ?string
    {
        foreach ($this->providerPatterns as $pattern => $providerName) {
            if (preg_match($pattern, $name)) {
                return $providerName;
            }
        }

        return null;
    }

    public function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $normalized = strtoupper($name);
        $normalized = preg_replace('/[^A-Z0-9]+/', ' ', $normalized) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        return $normalized;
    }

    public function computeDedupHash(?string $name, ?float $lat, ?float $lng): ?string
    {
        $nameHash = $this->normalizeName($name);
        if ($nameHash === '' || $lat === null || $lng === null) {
            return null;
        }

        $roundLat = round($lat, 3);
        $roundLng = round($lng, 3);

        return sha1($nameHash.'|'.$roundLat.'|'.$roundLng);
    }

    public function deriveTypeCharge(?int $kw): ?string
    {
        if ($kw === null || $kw < 0) {
            return null;
        }

        if ($kw >= 100) {
            return 'ultra_fast';
        }

        if ($kw >= 25) {
            return 'fast';
        }

        if ($kw >= 7) {
            return 'medium';
        }

        return 'standard';
    }

    /**
     * Resolve an existing production location this scraped record duplicates.
     */
    public function findDuplicate(SpkluScrapeRaw $row): ?SpkluLocation
    {
        if ($row->place_id) {
            $matched = $this->findByPlaceId($row->place_id);
            if ($matched) {
                return $matched;
            }
        }

        if ($row->dedup_hash) {
            $matched = $this->findByDedupHash($row->dedup_hash);
            if ($matched) {
                return $matched;
            }
        }

        return $this->findFuzzyMatch($row->nama_lokasi, $row->latitude, $row->longitude);
    }

    private function findByPlaceId(string $placeId): ?SpkluLocation
    {
        // Primary: match directly against production locations by place_id.
        $matched = SpkluLocation::query()->where('place_id', $placeId)->first();
        if ($matched) {
            return $matched;
        }

        // Fallback: a previously-approved staging row may already know the
        // production location id (e.g. place_id column added later).
        $existing = SpkluScrapeRaw::query()
            ->where('place_id', $placeId)
            ->whereIn('status', [SpkluScrapeRaw::STATUS_DUPLICATE, SpkluScrapeRaw::STATUS_APPROVED])
            ->whereNotNull('matched_spklu_location_id')
            ->first();

        if ($existing) {
            return SpkluLocation::find($existing->matched_spklu_location_id);
        }

        return null;
    }

    private function findByDedupHash(string $dedupHash): ?SpkluLocation
    {
        $existing = SpkluScrapeRaw::query()
            ->where('dedup_hash', $dedupHash)
            ->whereIn('status', [SpkluScrapeRaw::STATUS_DUPLICATE, SpkluScrapeRaw::STATUS_APPROVED])
            ->whereNotNull('matched_spklu_location_id')
            ->first();

        if ($existing) {
            return SpkluLocation::find($existing->matched_spklu_location_id);
        }

        return null;
    }

    private function findFuzzyMatch(?string $name, ?float $lat, ?float $lng): ?SpkluLocation
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === '' || $lat === null || $lng === null) {
            return null;
        }

        $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $candidates = SpkluLocation::query()
            ->select('spklu_locations.*')
            ->selectRaw($haversine.' AS distance')
            ->whereRaw($haversine.' <= 0.2')
            ->orderBy('distance')
            ->limit(5)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateName = $this->normalizeName($candidate->nama_lokasi);
            if ($candidateName === '') {
                continue;
            }

            similar_text($normalized, $candidateName, $percent);
            if ($percent >= 80) {
                return $candidate;
            }
        }

        return null;
    }
}
