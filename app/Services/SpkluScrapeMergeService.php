<?php

namespace App\Services;

use App\Models\SpkluChargerBox;
use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use Illuminate\Support\Facades\DB;

class SpkluScrapeMergeService
{
    public function __construct(private GeocodingService $geocoding) {}

    /**
     * Approve a staged scrape row by merging it into the production
     * spklu_locations / spklu_charger_boxes tables.
     *
     * Behaviour:
     *  - NEW (no production match) -> create a fresh production location.
     *  - DUPLICATE (matched_spklu_location_id set) -> update the existing
     *    production location, but only fill fields that are still empty so
     *    manual corrections are never overwritten. Google-sourced signals
     *    (rating / total_reviews) are always refreshed. New charger boxes
     *    that do not already exist are appended.
     */
    public function approve(SpkluScrapeRaw $row, array $overrides = []): SpkluLocation
    {
        if ($row->status === SpkluScrapeRaw::STATUS_APPROVED && $row->matched_spklu_location_id) {
            return SpkluLocation::findOrFail($row->matched_spklu_location_id);
        }

        return DB::connection('ev')->transaction(function () use ($row, $overrides) {
            $existing = $row->matched_spklu_location_id
                ? SpkluLocation::find($row->matched_spklu_location_id)
                : null;

            if ($existing) {
                $this->updateExistingLocation($existing, $row, $overrides);
                $this->appendMissingChargers($existing, $row);

                $row->update([
                    'status' => SpkluScrapeRaw::STATUS_APPROVED,
                    'matched_spklu_location_id' => $existing->id,
                ]);

                return $existing->fresh();
            }

            $location = SpkluLocation::create([
                'external_id' => $overrides['external_id'] ?? $this->nextExternalId(),
                'place_id' => $overrides['place_id'] ?? $row->place_id,
                'provider_id' => $overrides['provider_id'] ?? $row->guessed_provider_id,
                'provinsi' => $overrides['provinsi'] ?? $this->resolveProvinsi($row),
                'kabupaten_kota' => $overrides['kabupaten_kota'] ?? $this->resolveKabupatenKota($row),
                'nama_lokasi' => $overrides['nama_lokasi'] ?? $row->nama_lokasi,
                'alamat' => $overrides['alamat'] ?? $row->alamat,
                'latitude' => $row->latitude,
                'longitude' => $row->longitude,
                'type_charge' => $overrides['type_charge'] ?? $row->type_charge,
                'watt' => $overrides['watt'] ?? $this->resolveWatt($row),
                'status' => 1,
                'keterangan' => $overrides['keterangan'] ?? 'Dari hasil scrape (session: '.$row->scrape_session.')',
                'total_charger' => $row->total_charger,
                'total_konektor' => $row->total_konektor,
            ]);

            foreach ($row->chargers as $charger) {
                SpkluChargerBox::create([
                    'spklu_location_id' => $location->id,
                    'chargerbox_id' => $charger->connector_type,
                    'type_charge' => $charger->type_charge,
                    'nama_chargerbox' => $charger->connector_type,
                    'watt' => $charger->watt,
                    'jumlah_charger' => $charger->jumlah_charger,
                    'jumlah_konektor' => $charger->jumlah_konektor,
                ]);
            }

            $row->update([
                'status' => SpkluScrapeRaw::STATUS_APPROVED,
                'matched_spklu_location_id' => $location->id,
            ]);

            return $location;
        });
    }

    /**
     * Refresh an existing production location, filling only empty fields so
     * manual edits survive. Rating / reviews always update (Google-fresh).
     */
    private function updateExistingLocation(SpkluLocation $location, SpkluScrapeRaw $row, array $overrides): void
    {
        $patch = [];

        // Note: rating / total_reviews from Google are kept only in the raw
        // staging row; spklu_locations has no column for them.

        foreach (['place_id', 'alamat'] as $field) {
            if (empty($location->{$field}) && ! empty($row->{$field})) {
                $patch[$field] = $row->{$field};
            }
        }

        // Lat/lng: backfill when missing.
        if ($location->latitude === null && $row->latitude !== null) {
            $patch['latitude'] = $row->latitude;
        }
        if ($location->longitude === null && $row->longitude !== null) {
            $patch['longitude'] = $row->longitude;
        }

        // Classification: backfill when empty.
        if (empty($location->type_charge) && $row->type_charge !== null) {
            $patch['type_charge'] = $row->type_charge;
        }
        if (empty($location->watt) && ($row->max_kw !== null)) {
            $patch['watt'] = $this->resolveWatt($row);
        }
        if (empty($location->provider_id) && $row->guessed_provider_id !== null) {
            $patch['provider_id'] = $row->guessed_provider_id;
        }

        // Reviewer overrides win over everything (they are explicit choices).
        foreach ($overrides as $key => $value) {
            if (in_array($key, (new SpkluLocation)->getFillable(), true)) {
                $patch[$key] = $value;
            }
        }

        if ($patch) {
            $location->update($patch);
        }
    }

    /**
     * Append scraped charger boxes that are not already present on the
     * production location. Matching key: connector name + watt.
     */
    private function appendMissingChargers(SpkluLocation $location, SpkluScrapeRaw $row): void
    {
        $existing = $location->chargerBoxes()->get()->map(fn ($c) => strtolower(($c->nama_chargerbox ?? '').'|'.($c->watt ?? '')));

        foreach ($row->chargers as $charger) {
            $key = strtolower(($charger->connector_type ?? '').'|'.($charger->watt ?? ''));
            if ($existing->contains($key)) {
                continue;
            }

            SpkluChargerBox::create([
                'spklu_location_id' => $location->id,
                'chargerbox_id' => $charger->connector_type,
                'type_charge' => $charger->type_charge,
                'nama_chargerbox' => $charger->connector_type,
                'watt' => $charger->watt,
                'jumlah_charger' => $charger->jumlah_charger,
                'jumlah_konektor' => $charger->jumlah_konektor,
            ]);
        }
    }

    public function reject(SpkluScrapeRaw $row): void
    {
        $row->update(['status' => SpkluScrapeRaw::STATUS_REJECTED]);
    }

    public function nextExternalId(): int
    {
        return (int) (SpkluLocation::query()->max('external_id') ?? 0) + 1;
    }

    public function resolveProvinsi(SpkluScrapeRaw $row): string
    {
        return strtoupper($this->reverseGeocode($row)['provinsi'] ?? '');
    }

    public function resolveKabupatenKota(SpkluScrapeRaw $row): ?string
    {
        return $this->reverseGeocode($row)['kabupaten_kota'] ?? null;
    }

    private function reverseGeocode(SpkluScrapeRaw $row): ?array
    {
        if ($row->latitude === null || $row->longitude === null) {
            return null;
        }

        try {
            $result = $this->geocoding->reverseGeocode($row->latitude, $row->longitude);
            if (! $result) {
                return null;
            }

            $components = $result['address_components']['address'] ?? $result['address_components'] ?? [];

            return [
                'provinsi' => $components['state'] ?? $components['province'] ?? null,
                'kabupaten_kota' => $components['county'] ?? $components['city'] ?? $components['town'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveWatt(SpkluScrapeRaw $row): ?string
    {
        if ($row->max_kw !== null) {
            return $row->max_kw.' kW';
        }

        return null;
    }
}
