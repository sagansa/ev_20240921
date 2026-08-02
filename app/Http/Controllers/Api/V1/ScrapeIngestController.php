<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ScrapeIngestRequest;
use App\Models\Provider;
use App\Models\SpkluScrapeRaw;
use App\Models\SpkluScrapeRawCharger;
use App\Services\ScrapeDedupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScrapeIngestController extends Controller
{
    public function __construct(private ScrapeDedupService $dedup) {}

    public function ingest(ScrapeIngestRequest $request)
    {
        if (! Auth::user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $session = $request->input('session');
        $inserted = 0;
        $errors = [];

        DB::connection('ev')->transaction(function () use ($request, $session, &$inserted, &$errors) {
            foreach ($request->input('places', []) as $placeData) {
                try {
                    $latitude = isset($placeData['latitude']) ? (float) $placeData['latitude'] : null;
                    $longitude = isset($placeData['longitude']) ? (float) $placeData['longitude'] : null;
                    $name = $placeData['nama_lokasi'] ?? '';
                    $dedupHash = $this->dedup->computeDedupHash($name, $latitude, $longitude);

                    $existing = SpkluScrapeRaw::query()
                        ->where('scrape_session', $session)
                        ->where(function ($q) use ($placeData, $dedupHash) {
                            if (! empty($placeData['place_id'])) {
                                $q->orWhere('place_id', $placeData['place_id']);
                            }
                            if ($dedupHash) {
                                $q->orWhere('dedup_hash', $dedupHash);
                            }
                        })
                        ->first();

                    if ($existing) {
                        $duplicates++;

                        continue;
                    }

                    // Prefer an explicit provider hint from the extension;
                    // fall back to inferring it from the place name against the
                    // full provider table (returns a Provider model or null).
                    $provider = null;
                    if (! empty($placeData['provider_name'])) {
                        $provider = Provider::where('name', $placeData['provider_name'])->first();
                    }
                    if (! $provider) {
                        $provider = $this->dedup->guessProvider($name);
                    }
                    $providerName = $provider?->name;

                    $maxKw = isset($placeData['max_kw']) ? (int) $placeData['max_kw'] : null;
                    $typeCharge = $placeData['type_charge'] ?? $this->dedup->deriveTypeCharge($maxKw);

                    $row = SpkluScrapeRaw::create([
                        'place_id' => $placeData['place_id'] ?? null,
                        'nama_lokasi' => $name,
                        'alamat' => $placeData['alamat'] ?? null,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'rating' => isset($placeData['rating']) ? (float) $placeData['rating'] : null,
                        'total_reviews' => $placeData['total_reviews'] ?? null,
                        'phone' => $placeData['phone'] ?? null,
                        'opening_hours' => $placeData['opening_hours'] ?? null,
                        'website' => $placeData['website'] ?? null,
                        'provider_name' => $providerName,
                        'guessed_provider_id' => $provider?->id,
                        'type_charge' => $typeCharge,
                        'max_kw' => $maxKw,
                        'total_charger' => (int) ($placeData['total_charger'] ?? 0),
                        'total_konektor' => (int) ($placeData['total_konektor'] ?? 0),
                        'raw_payload' => $placeData,
                        'dedup_hash' => $dedupHash,
                        'status' => SpkluScrapeRaw::STATUS_NEW,
                        'scrape_session' => $session,
                    ]);

                    foreach ($placeData['chargers'] ?? [] as $chargerData) {
                        $chargerKw = isset($chargerData['power_kw']) ? (int) $chargerData['power_kw'] : null;
                        SpkluScrapeRawCharger::create([
                            'scrape_raw_id' => $row->id,
                            'connector_type' => $chargerData['connector_type'] ?? null,
                            'power_kw' => $chargerKw,
                            'watt' => $chargerKw !== null ? $chargerKw.' kW' : null,
                            'type_charge' => $chargerData['type_charge'] ?? $this->dedup->deriveTypeCharge($chargerKw),
                            'jumlah_charger' => (int) ($chargerData['jumlah_charger'] ?? 1),
                            'jumlah_konektor' => $chargerData['jumlah_konektor'] ?? '1',
                        ]);
                    }

                    // Every ingested row starts as NEW. Matching against the
                    // canonical spklu_locations dataset is advisory only and
                    // happens in the Filament review UI (recommendCandidates).
                    // The scrape pipeline never mutates production.
                    $inserted++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'nama_lokasi' => $placeData['nama_lokasi'] ?? '',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Scrape data ingested',
            'data' => [
                'inserted' => $inserted,
                'errors' => $errors,
            ],
        ]);
    }
}
