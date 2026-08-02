<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use Illuminate\Support\Facades\Cache;

class ScrapeDedupService
{
    /**
     * Infer the provider of a scraped place by matching its name against EVERY
     * provider in the database (not a hardcoded list). Returns the Provider
     * model so the caller can store `guessed_provider_id` directly.
     *
     * Matching rules:
     *  - Case-insensitive, against the provider's alphanumeric tokens so that
     *    "INVI" matches "INVI Charging Station" and "Toyota & Lexus" matches
     *    "...Toyota..." or "...Lexus...".
     *  - Longest provider names are tried first so that "Toyota & Lexus" wins
     *    over a bare "Toyota" when both would match.
     *  - Generic filler tokens (Mall, Hotel, Rumah, Kantor, Parking, Astra)
     *    are only matched as exact-ish namesakes, never as a substring of the
     *    place name, to avoid false positives.
     */
    public function guessProvider(string $name): ?Provider
    {
        $haystack = $this->normalizeName($name);
        if ($haystack === '') {
            return null;
        }

        // Fast path: a single strong token ("PLN", "SHELL", ...) in the place
        // name maps straight to its provider, even when the provider's stored
        // name is compound ("PLN Mobile").
        $words = explode(' ', $haystack);
        $providers = $this->providersForMatching();
        $byName = [];
        foreach ($providers as $entry) {
            $byName[$this->normalizeName($entry['model']->name)] = $entry['model'];
        }
        foreach ($words as $word) {
            if (isset(self::STRONG_TOKEN_ALIASES[$word])) {
                $providerName = $this->normalizeName(self::STRONG_TOKEN_ALIASES[$word]);
                if (isset($byName[$providerName])) {
                    return $byName[$providerName];
                }
            }
        }

        // Full match: every token of the provider name must appear in the
        // place name. Providers are ordered longest-first so "Toyota & Lexus"
        // wins over a bare "Toyota".
        foreach ($providers as $entry) {
            if ($this->nameMatchesProvider($haystack, $entry)) {
                return $entry['model'];
            }
        }

        return null;
    }

    public function guessProviderName(string $name): ?string
    {
        return $this->guessProvider($name)?->name;
    }

    /**
     * Providers from DB, cached briefly, sorted longest-name-first and with
     * the "generic" providers (Mall/Hotel/...) flagged so they use stricter
     * matching. Returns an array of [Provider model, tokens[], generic bool].
     */
    private function providersForMatching(): array
    {
        return Cache::remember('scrape:providers:'.Provider::count(), now()->addHour(), function () {
            $generic = ['mall', 'hotel', 'rumah', 'kantor', 'parking', 'astra', 'igreen'];

            return Provider::query()
                ->orderByRaw('LENGTH(name) DESC')
                ->get()
                ->map(fn ($p) => [
                    'model' => $p,
                    'tokens' => $this->extractTokens($p->name),
                    'generic' => in_array(strtolower($p->name), $generic, true),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * "Toyota & Lexus" -> ["TOYOTA","LEXUS"]. Drops "&", "and", "+", punctuation.
     */
    private function extractTokens(string $name): array
    {
        $clean = preg_replace('/[^A-Z0-9 ]/i', ' ', strtoupper($name));
        $tokens = array_values(array_filter(explode(' ', $clean ?? ''), fn ($t) => ! in_array($t, ['', 'AND'], true)));

        return $tokens;
    }

    /**
     * Some providers are stored under a compound name ("PLN Mobile") but the
     * place name only mentions the strong single token ("PLN"). Map such
     * strong tokens to their provider name so they still match.
     */
    private const STRONG_TOKEN_ALIASES = [
        'PLN' => 'PLN Mobile',
        'PERTAMINA' => 'Pertamina',
        'SHELL' => 'Shell',
        'HYUNDAI' => 'Hyundai',
        'WULING' => 'Wuling',
        'LEXUS' => 'Toyota & Lexus',
        'TOYOTA' => 'Toyota & Lexus',
    ];

    private function nameMatchesProvider(string $haystack, array $entry): bool
    {
        $tokens = $entry['tokens'];
        if (empty($tokens)) {
            return false;
        }

        // Generic providers (Mall, Hotel, ...) only match when the haystack
        // literally starts with the provider name — avoids flagging every
        // "Hotel X" location as provider "Hotel".
        if ($entry['generic']) {
            return str_starts_with($haystack, $this->normalizeName($entry['model']->name));
        }

        // All tokens of the provider must appear in the place name.
        foreach ($tokens as $token) {
            if (! preg_match('/\b'.preg_quote($token, '/').'\b/i', $haystack)) {
                return false;
            }
        }

        return true;
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
