<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\SpkluLocationResource;
use App\Models\SpkluLocation;
use App\Models\SpkluScrapeRaw;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SpkluLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = SpkluLocation::with(['chargerBoxes', 'provider']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lokasi', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        if ($request->filled('provinsi')) {
            $query->where('provinsi', trim($request->provinsi));
        }

        if ($request->filled('type_charge')) {
            $query->where('type_charge', $request->type_charge);
        }

        if ($request->filled('watt')) {
            $query->where('watt', $request->watt);
        }

        $lat = $request->filled('lat') ? (float) $request->lat : null;
        $lng = $request->filled('lng') ? (float) $request->lng : null;
        $radius = $request->filled('radius') ? (float) $request->radius : null;

        if ($lat !== null && $lng !== null && $radius !== null) {
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

            $query->select('spklu_locations.*')
                ->selectRaw($haversine . ' AS distance')
                ->whereRaw($haversine . ' <= ' . $radius)
                ->orderBy('distance');
        }

        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        $locations = $query->paginate($perPage, ['*'], 'page', $page);

        // Display-layer UNION: include APPROVED scrape rows that are NOT linked
        // to a production location (linked ones are already represented by the
        // production row above). These never come from spklu_locations — they
        // are surfaced directly from the scrape staging table, so the canonical
        // JSON dataset stays untouched.
        if ($request->boolean('include_scrape', true)) {
            $scrapeRows = $this->approvedScrapeForDisplay($request, $lat, $lng, $radius);
            if ($scrapeRows->isNotEmpty()) {
                // Append to the paginator's underlying collection. They are
                // unsaved SpkluLocation instances so the existing resource
                // serializes them transparently.
                $merged = $locations->getCollection()->concat($scrapeRows);
                $locations->setCollection($merged);
            }
        }

        return SpkluLocationResource::collection($locations)
            ->additional(['status' => 'success']);
    }

    /**
     * Build APPROVED scrape rows as unsaved SpkluLocation models so the
     * existing SpkluLocationResource serializes them transparently. A virtual
     * external_id ("scrape-{id}") avoids collisions with production ids.
     */
    private function approvedScrapeForDisplay(Request $request, ?float $lat, ?float $lng, ?float $radius): Collection
    {
        $q = SpkluScrapeRaw::query()
            ->with(['chargers', 'guessedProvider'])
            ->where('status', SpkluScrapeRaw::STATUS_APPROVED)
            ->whereNull('linked_spklu_location_id'); // linked = shown via production

        if ($request->filled('search')) {
            $search = $request->search;
            $q->where(function ($qq) use ($search) {
                $qq->where('nama_lokasi', 'like', "%{$search}%")
                   ->orWhere('alamat', 'like', "%{$search}%");
            });
        }
        if ($request->filled('type_charge')) {
            $q->where('type_charge', $request->type_charge);
        }

        if ($lat !== null && $lng !== null && $radius !== null) {
            $q->whereRaw("(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude)))) <= $radius");
        }

        return $q->limit(500)->get()->map(function ($r) {
            // Unsaved model — purely for serialization shape.
            $loc = new SpkluLocation([
                'external_id' => 'scrape-'.$r->id,
                'provinsi' => null,
                'kabupaten_kota' => null,
                'nama_lokasi' => $r->nama_lokasi,
                'alamat' => $r->alamat,
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'type_charge' => $r->type_charge,
                'watt' => $r->max_kw !== null ? $r->max_kw.' kW' : null,
                'status' => 1,
                'keterangan' => 'Sumber: Google Maps scrape',
                'total_charger' => $r->total_charger,
                'total_konektor' => $r->total_konektor,
                'provider_id' => $r->guessed_provider_id,
            ]);
            // Load the same relations the resource uses.
            $loc->setRelation('provider', $r->guessedProvider);
            $loc->setRelation('chargerBoxes', $r->chargers->map(function ($c) {
                return new \App\Models\SpkluChargerBox([
                    'chargerbox_id' => $c->connector_type,
                    'nama_chargerbox' => trim(($c->connector_type ?? '').' '.($c->watt ?? '')),
                    'type_charge' => $c->type_charge,
                    'watt' => $c->watt,
                    'jumlah_charger' => $c->jumlah_charger,
                    'jumlah_konektor' => $c->jumlah_konektor,
                ]);
            }));

            return $loc;
        });
    }

    public function show($id)
    {
        // Mobile mengirim id = external_id (lihat SpkluLocationResource::id),
        // jadi lookup harus berdasarkan external_id, bukan PK.
        $location = SpkluLocation::with(['chargerBoxes', 'provider'])
            ->where('external_id', $id)
            ->firstOrFail();

        return SpkluLocationResource::make($location)
            ->additional(['status' => 'success']);
    }

    public function metaFilters()
    {
        $provinces = SpkluLocation::select('provinsi')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi');

        $chargeTypes = SpkluLocation::select('type_charge')
            ->distinct()
            ->orderBy('type_charge')
            ->pluck('type_charge')
            ->filter();

        return response()->json([
            'status' => 'success',
            'data' => [
                'provinces' => $provinces,
                'charge_types' => $chargeTypes->values(),
            ],
        ]);
    }
}
