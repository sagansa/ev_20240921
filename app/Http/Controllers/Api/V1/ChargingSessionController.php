<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\ChargingSessionResource;
use App\Models\Charge;
use App\Models\ChargingStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD sesi charging (Charge) untuk mobile SPKLU — pengganti logbook yang
 * dihapus. Mendukung dua sumber lokasi: charging_stations (mobile) atau
 * charger_locations (legacy Filament). Vehicle opsional; field pengukuran
 * opsional untuk input cepat di lapangan.
 */
class ChargingSessionController extends Controller
{
    /**
     * List sesi milik user terautentikasi.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Charge::where('charges.user_id', Auth::id())
            ->with(['vehicle.typeVehicle', 'vehicle.modelVehicle', 'chargingStation', 'chargerLocation', 'chargerLocation.provider', 'charger.typeCharger']);

        if ($request->filled('charging_station_id')) {
            $query->where('charging_station_id', $request->charging_station_id);
        }
        if ($request->filled('charger_location_id')) {
            $query->where('charger_location_id', $request->charger_location_id);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('type_vehicle_id')) {
            $typeId = $request->type_vehicle_id;
            $query->whereHas('vehicle', function ($sub) use ($typeId) {
                $sub->where('type_vehicle_id', $typeId);
            });
        }
        if ($request->filled('model_vehicle_id')) {
            $modelId = $request->model_vehicle_id;
            $query->whereHas('vehicle', function ($sub) use ($modelId) {
                $sub->where('model_vehicle_id', $modelId)
                    ->orWhereHas('typeVehicle', function ($typeSub) use ($modelId) {
                        $typeSub->where('model_vehicle_id', $modelId);
                    });
            });
        }
        if ($request->filled('charging_type')) {
            $this->applyChargingTypeScope($query, strtoupper($request->charging_type));
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // paginate() utk performa (hindari load all ratusan sesi sekaligus),
        // lalu reshape ke kontrak mobile: `data:[...]` array murni + info
        // pagination di `meta`. Paginator bawaan Laravel membungkus data jadi
        // `{data, links, meta}` yang gagal deserialize di mobile.
        $perPage = (int) ($request->per_page ?? 20);
        $perPage = max(1, min(100, $perPage));
        $paginator = $query->orderByDesc('date')->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Charging sessions retrieved successfully',
            'data' => ChargingSessionResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * Buat sesi charging baru. Vehicle & field pengukuran opsional — input
     * cepat mobile hanya butuh minimal: date, salah satu lokasi, dan (biasanya)
     * kWh + total_cost.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSession($request, true);

        // Auto-fill data "sebelum" dari sesi terakhir kendaraan yg sama —
        // supaya perhitungan turunan (km trip, delta battery → losses,
        // Rp/km, kWh/km) konsisten tanpa user mengingat/mengisi field tsb.
        // `km_before` ← sesi terakhir `km_now`; `finish_charging_before` ←
        // sesi terakhir `finish_charging_now`. Override input mobile (field
        // tsb sudah tidak ada di form).
        if (empty($validated['vehicle_id'])) {
            $firstVehicle = Auth::user()?->vehicles()->first();
            if ($firstVehicle) {
                $validated['vehicle_id'] = $firstVehicle->id;
            }
        }

        if (! empty($validated['vehicle_id'])) {
            $previous = $this->latestForVehicle($validated['vehicle_id']);
            $validated['km_before'] = $previous?->km_now;
            $validated['finish_charging_before'] = $previous?->finish_charging_now;
        }

        if (empty($validated['charging_station_id']) && ! empty($request->input('station_name'))) {
            $customLoc = \App\Models\ChargerLocation::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'name' => $request->input('station_name'),
                ],
                [
                    'address' => $request->input('station_address'),
                    'data_source' => 'user_custom',
                    'status' => 1,
                    'location_on' => 1,
                ]
            );
            $validated['charger_location_id'] = $customLoc->id;
        }

        $charge = Charge::create(array_merge(
            ['user_id' => Auth::id()],
            $validated,
            $this->stationSnapshot($request),
        ));

        $charge->load(['vehicle', 'chargingStation', 'chargerLocation']);

        return response()->json([
            'success' => true,
            'message' => 'Charging session created successfully',
            'data' => new ChargingSessionResource($charge),
        ], 201);
    }

    public function show(Request $request, Charge $chargingSession): JsonResponse
    {
        if (! $this->owns($chargingSession)) {
            return $this->forbidden();
        }
        $chargingSession->load(['vehicle', 'chargingStation', 'chargerLocation']);

        return response()->json([
            'success' => true,
            'message' => 'Charging session retrieved successfully',
            'data' => new ChargingSessionResource($chargingSession),
        ]);
    }

    public function update(Request $request, Charge $chargingSession): JsonResponse
    {
        if (! $this->owns($chargingSession)) {
            return $this->forbidden();
        }
        $validated = $this->validateSession($request, requireOwnership: true, partial: true);

        $data = $validated;
        // Re-snapshot bila charging_station_id berubah (termasuk dihapus).
        if (array_key_exists('charging_station_id', $validated) || array_key_exists('station_name', $validated)) {
            $data = array_merge($data, $this->stationSnapshot($request));
        }
        $chargingSession->update($data);
        $chargingSession->load(['vehicle', 'chargingStation', 'chargerLocation']);

        return response()->json([
            'success' => true,
            'message' => 'Charging session updated successfully',
            'data' => new ChargingSessionResource($chargingSession),
        ]);
    }

    public function destroy(Request $request, Charge $chargingSession): JsonResponse
    {
        if (! $this->owns($chargingSession)) {
            return $this->forbidden();
        }
        $chargingSession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Charging session deleted successfully',
        ]);
    }

    /**
     * Sesi charging terakhir milik user untuk kendaraan tertentu. Kini dipakai
     * internal utk auto-fill `km_before`/`finish_charging_before` di store();
     * tetap diekspos sebagai endpoint utk kompatibilitas (mis. debug/admin).
     */
    public function latest(Request $request): JsonResponse
    {
        $request->validate(['vehicle_id' => 'required']);

        $session = $this->latestForVehicle($request->vehicle_id)?->load(['vehicle', 'chargingStation', 'chargerLocation']);

        return response()->json([
            'success' => true,
            'message' => 'Latest charging session retrieved successfully',
            'data' => $session ? new ChargingSessionResource($session) : null,
        ]);
    }

    /**
     * Query sesi terakhir milik user utk kendaraan tertentu (shared oleh
     * endpoint latest() dan store() auto-fill). Ordered by date lalu
     * created_at agar sesi hari sama urut konsisten.
     */
    private function latestForVehicle(string $vehicleId): ?Charge
    {
        return Charge::where('charges.user_id', Auth::id())
            ->where('vehicle_id', $vehicleId)
            ->latest('date')
            ->latest('created_at')
            ->first();
    }

    /**
     * Ringkasan analytics untuk dashboard mobile — total sesi, kWh, biaya,
     * jarak, efisiensi (kWh/100km, cost/km), dan penghematan vs BBM.
     */
    public function analytics(Request $request): JsonResponse
    {
        $query = Charge::where('charges.user_id', Auth::id());
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('type_vehicle_id')) {
            $typeId = $request->type_vehicle_id;
            $query->whereHas('vehicle', function ($sub) use ($typeId) {
                $sub->where('type_vehicle_id', $typeId);
            });
        }
        if ($request->filled('model_vehicle_id')) {
            $modelId = $request->model_vehicle_id;
            $query->whereHas('vehicle', function ($sub) use ($modelId) {
                $sub->where('model_vehicle_id', $modelId)
                    ->orWhereHas('typeVehicle', function ($typeSub) use ($modelId) {
                        $typeSub->where('model_vehicle_id', $modelId);
                    });
            });
        }
        if ($request->filled('charging_type')) {
            $this->applyChargingTypeScope($query, strtoupper($request->charging_type));
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $totalSessions = (clone $query)->count();
        $totalKwh = (float) ((clone $query)->sum('kWh') ?? 0);
        $totalCost = (float) ((clone $query)->sum('total_cost') ?? 0);

        // Kalkulasi Total Km persis seperti Filament ChargeStats widget ($kmNow - $kmBefore)
        $kmNowSum = (float) ((clone $query)->sum('km_now') ?? 0);
        $kmBeforeSum = (float) ((clone $query)->sum('km_before') ?? 0);
        $totalKm = max($kmNowSum - $kmBeforeSum, 0);

        // Fallback per-sesi jika km_before 0 atau belum terisi
        if ($totalKm <= 0) {
            $sessions = (clone $query)->whereNotNull('km_now')->where('km_now', '>', 0)->get();
            if ($sessions->count() >= 2) {
                $minKm = $sessions->min('km_now');
                $maxKm = $sessions->max('km_now');
                $totalKm = (float) max($maxKm - $minKm, 0);
            } elseif ($sessions->count() == 1) {
                $first = $sessions->first();
                $totalKm = (float) max(($first->km_now - ($first->km_before ?? 0)), 0);
            }
        }

        // Efisiensi & biaya
        $kwhPer100km = $totalKm > 0 ? ($totalKwh / $totalKm) * 100 : 0;
        $kmPerKwh = $totalKwh > 0 ? $totalKm / $totalKwh : 0;
        $costPerKm = $totalKm > 0 ? $totalCost / $totalKm : 0;
        $costPerKwh = $totalKwh > 0 ? $totalCost / $totalKwh : 0;
        $estimatedBbmCost = ($totalKm / 12) * 13700;
        $totalSavings = max($estimatedBbmCost - $totalCost, 0);

        return response()->json([
            'success' => true,
            'message' => 'Analytics retrieved successfully',
            'data' => [
                'total_sessions' => $totalSessions,
                'total_energy_kwh' => round($totalKwh, 2),
                'total_cost' => round($totalCost, 0),
                'total_distance_km' => round($totalKm, 1),
                'cost_per_kwh' => round($costPerKwh, 0),
                'cost_per_km' => round($costPerKm, 0),
                'kwh_per_100km' => round($kwhPer100km, 2),
                'km_per_kwh' => round($kmPerKwh, 2),
                'estimated_bbm_cost' => round($estimatedBbmCost, 0),
                'total_savings' => round($totalSavings, 0),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function owns(Charge $charge): bool
    {
        return (string) $charge->user_id === (string) Auth::id();
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access to charging session',
        ], 403);
    }

    /**
     * Filter AC/DC. Cascade sumber kebenaran dgn PRIORITAS EKSKLUSIF: setiap
     * tingkat fallback hanya berlaku bila tingkat yang lebih definitif TIDAK
     * tersedia untuk sesi itu — mencegah sumber lemah (snapshot nama) menarik
     * sesi yang sudah punya sumber definitif (mis. charger DC dgn nama AC).
     *
     *  1. charger.typeCharger.name (enum 'AC'/'DC') — PRIMARY. Saat input via
     *     Filament admin, user memilih Charger spesifik (charger_id); Charger
     *     ber-relasi ke TypeCharger dgn name enum.
     *  2. chargingStation.type_charge / .chargers.type_charge canonical —
     *     fallback utk sesi mobile SPKLU (charging_station_id, tanpa charger_id).
     *     medium/standard = AC, fast/ultra_fast = DC.
     *  3. Snapshot nama — fallback terakhir utk sesi tanpa relasi apa pun.
     *
     * Keyakinan "sumber definitif ada" dipakai sebagai penutup fallback: bila
     * charger_id terisi, snapshot tidak dipakai lagi (tidak ambigu).
     */
    private function applyChargingTypeScope($query, string $cType): void
    {
        $dcTokens = ['DC', 'FAST', 'ULTRA', 'CCS', 'CHADEMO', 'SUPERCHARGER',
                     '50KW', '60KW', '100KW', '120KW', '150KW', '200KW'];
        $dcStationTypes = ['fast', 'ultra_fast', 'ultrafast', 'fastcharging', 'ultrafastcharging'];
        $acStationTypes = ['medium', 'standard', 'mediumcharging', 'slowcharging', 'slow', 'ac'];

        // TypeCharger.name di produksi = NAMA KONEKTOR (bukan enum AC/DC):
        //   DC: "CCS2", "Chademo", "DC GBT", dst. (prefix "DC" atau nama DC dikenal)
        //   AC: "Type 2", "AC GBT", dst. (prefix "AC" atau nama AC dikenal)
        $dcConnectors = function ($tc) {
            $tc->where(function ($w) {
                $w->where('name', 'LIKE', 'DC%')
                  ->orWhereIn('name', ['CCS2', 'CCS', 'Chademo', 'CHAdeMO', 'Supercharger']);
            });
        };
        $acConnectors = function ($tc) {
            $tc->where(function ($w) {
                $w->where('name', 'LIKE', 'AC%')
                  ->orWhereIn('name', ['Type 2', 'Type2', 'J1772', 'GB/T AC']);
            });
        };

        if ($cType === 'DC') {
            $query->where(function ($q) use ($dcConnectors, $dcTokens, $dcStationTypes) {
                // (1) Charger → TypeCharger (connector name) DC.
                $q->whereHas('charger.typeCharger', $dcConnectors)
                // (2) Tanpa charger_id, tapi dgn station canonical DC.
                  ->orWhere(function ($s) use ($dcStationTypes) {
                      $s->whereNull('charger_id')
                        ->where(function ($ss) use ($dcStationTypes) {
                            $ss->whereHas('chargingStation', fn ($st) => $st->whereIn('type_charge', $dcStationTypes))
                               ->orWhereHas('chargingStation.chargers', fn ($cq) => $cq->whereIn('type_charge', $dcStationTypes));
                        });
                  })
                // (3) Tanpa charger_id & charging_station_id → snapshot mengandung token DC.
                  ->orWhere(function ($s) use ($dcTokens) {
                      $s->whereNull('charger_id')
                        ->whereNull('charging_station_id')
                        ->where(function ($snap) use ($dcTokens) {
                            foreach ($dcTokens as $token) {
                                $snap->orWhere('station_name_snapshot', 'LIKE', "%{$token}%")
                                     ->orWhere('station_provider_snapshot', 'LIKE', "%{$token}%");
                            }
                        });
                  });
            });
        } elseif ($cType === 'AC') {
            $query->where(function ($q) use ($acConnectors, $dcTokens, $acStationTypes) {
                // (1) Charger → TypeCharger (connector name) AC.
                $q->whereHas('charger.typeCharger', $acConnectors)
                // (2) Tanpa charger_id, tapi dgn station canonical AC.
                  ->orWhere(function ($s) use ($acStationTypes) {
                      $s->whereNull('charger_id')
                        ->where(function ($ss) use ($acStationTypes) {
                            $ss->whereHas('chargingStation', fn ($st) => $st->whereIn('type_charge', $acStationTypes))
                               ->orWhereHas('chargingStation.chargers', fn ($cq) => $cq->whereIn('type_charge', $acStationTypes));
                        });
                  })
                // (3) Tanpa charger_id & charging_station_id → snapshot bersih token DC.
                //     NULL-safe: (col IS NULL OR col NOT LIKE token) per kolom/token.
                  ->orWhere(function ($s) use ($dcTokens) {
                      $s->whereNull('charger_id')
                        ->whereNull('charging_station_id')
                        ->where(function ($c) use ($dcTokens) {
                            foreach ($dcTokens as $token) {
                                $c->where(function ($cc) use ($token) {
                                    $cc->whereNull('station_name_snapshot')
                                       ->orWhere('station_name_snapshot', 'NOT LIKE', "%{$token}%");
                                })->where(function ($cc) use ($token) {
                                    $cc->whereNull('station_provider_snapshot')
                                       ->orWhere('station_provider_snapshot', 'NOT LIKE', "%{$token}%");
                                });
                            }
                        });
                  });
            });
        }
    }

    /**
     * Validasi sesi. `partial=true` (update) pakai aturan `sometimes`.
     * Vehicle opsional: jika diisi, cek ownership. Field pengukuran opsional
     * (diisi default 0/null oleh model saat tidak dikirim).
     */
    private function validateSession(Request $request, bool $requireOwnership, bool $partial = false): array
    {
        $sometimes = $partial ? 'sometimes|' : '';
        $rules = [
            'vehicle_id' => [$sometimes.'nullable', function ($attr, $value, $fail) use ($requireOwnership) {
                if ($requireOwnership && filled($value)) {
                    $owns = Auth::user()->vehicles()->where('vehicles.id', $value)->exists();
                    if (! $owns) {
                        $fail('Unauthorized access to vehicle.');
                    }
                }
            }],
            'charging_station_id' => $sometimes.'nullable|integer',
            'charger_location_id' => $sometimes.'nullable|uuid',
            'charger_id' => $sometimes.'nullable|uuid',
            'station_name' => $sometimes.'nullable|string|max:255',
            'station_address' => $sometimes.'nullable|string|max:500',
            'station_provider' => $sometimes.'nullable|string|max:255',
            'date' => $sometimes.'nullable|date',
            'km_before' => $sometimes.'nullable|numeric|min:0',
            'km_now' => $sometimes.'nullable|numeric|min:0',
            'start_charging_now' => $sometimes.'nullable|numeric|min:0',
            'finish_charging_now' => $sometimes.'nullable|numeric|min:0',
            'finish_charging_before' => $sometimes.'nullable|numeric|min:0',
            'is_finish_charging' => $sometimes.'boolean',
            'kwh' => $sometimes.'nullable|numeric|min:0',
            'is_kwh_measured' => $sometimes.'boolean',
            'parking' => $sometimes.'nullable|numeric|min:0',
            'street_lighting_tax' => $sometimes.'nullable|numeric|min:0',
            'value_added_tax' => $sometimes.'nullable|numeric|min:0',
            'admin_cost' => $sometimes.'nullable|numeric|min:0',
            'total_cost' => $sometimes.'nullable|numeric|min:0',
        ];

        $validated = $request->validate($rules);

        // Map mobile field `kwh` → kolom legacy `kWh`.
        if (array_key_exists('kwh', $validated)) {
            $validated['kWh'] = $validated['kwh'];
            unset($validated['kwh']);
        }

        // Map custom station fields → snapshot columns jika diinput manual
        if (! empty($validated['station_name'])) {
            $validated['station_name_snapshot'] = $validated['station_name'];
            unset($validated['station_name']);
        }
        if (! empty($validated['station_address'])) {
            $validated['station_address_snapshot'] = $validated['station_address'];
            unset($validated['station_address']);
        }
        if (! empty($validated['station_provider'])) {
            $validated['station_provider_snapshot'] = $validated['station_provider'];
            unset($validated['station_provider']);
        }

        return $validated;
    }

    /**
     * Snapshot denormalized dari charging_stations saat sesi dibuat/diupdate.
     * Bila charging_station_id diberikan & station ditemukan, snapshot mengisi
     * station_*; jika tidak, gunakan snapshot manual jika ada.
     */
    private function stationSnapshot(Request $request): array
    {
        $stationId = $request->input('charging_station_id');
        if ($stationId) {
            $station = ChargingStation::find($stationId);
            if ($station) {
                return [
                    'station_name_snapshot' => $station->nama_lokasi,
                    'station_address_snapshot' => $station->alamat,
                    'station_lat_snapshot' => $station->latitude,
                    'station_lng_snapshot' => $station->longitude,
                    'station_provider_snapshot' => $station->provider_name,
                ];
            }
        }

        return [
            'station_name_snapshot' => $request->input('station_name'),
            'station_address_snapshot' => $request->input('station_address'),
            'station_lat_snapshot' => null,
            'station_lng_snapshot' => null,
            'station_provider_snapshot' => $request->input('station_provider'),
        ];
    }
}
