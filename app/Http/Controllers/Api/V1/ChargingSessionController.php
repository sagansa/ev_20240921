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
            ->with(['vehicle', 'chargingStation', 'chargerLocation']);

        if ($request->has('charging_station_id')) {
            $query->where('charging_station_id', $request->charging_station_id);
        }
        if ($request->has('charger_location_id')) {
            $query->where('charger_location_id', $request->charger_location_id);
        }
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // get() (bukan paginate()) supaya Resource::collection() menghasilkan
        // array murni `data: [...]`, cocok dgn kontrak mobile. Paginator justru
        // membungkus data jadi `{data, links, meta}` yang gagal deserialize.
        $entries = $query->orderByDesc('date')->get();

        return response()->json([
            'success' => true,
            'message' => 'Charging sessions retrieved successfully',
            'data' => ChargingSessionResource::collection($entries),
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
        if (! empty($validated['vehicle_id'])) {
            $previous = $this->latestForVehicle($validated['vehicle_id']);
            $validated['km_before'] = $previous?->km_now;
            $validated['finish_charging_before'] = $previous?->finish_charging_now;
        }

        $charge = Charge::create(array_merge(
            ['user_id' => Auth::id()],
            $validated,
            $this->stationSnapshot($validated['charging_station_id'] ?? null),
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
        if (array_key_exists('charging_station_id', $validated)) {
            $data = array_merge($data, $this->stationSnapshot($validated['charging_station_id']));
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
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $totalSessions = (clone $query)->count();
        $totalKwh = (float) (clone $query)->sum('kWh');
        $totalCost = (float) (clone $query)->sum('total_cost');
        $kmNow = (float) (clone $query)->sum('km_now');
        $kmBefore = (float) (clone $query)->sum('km_before');
        $totalKm = max($kmNow - $kmBefore, 0);

        // Efisiensi & savings vs BBM (Pertamax ~Rp 13.700/L, 12 km/L).
        $kwhPer100km = $totalKm > 0 ? ($totalKwh / $totalKm) * 100 : 0;
        $costPerKm = $totalKm > 0 ? $totalCost / $totalKm : 0;
        $estimatedBbmCost = ($totalKm / 12) * 13700;
        $totalSavings = $estimatedBbmCost - $totalCost;

        return response()->json([
            'success' => true,
            'message' => 'Analytics retrieved successfully',
            'data' => [
                'total_sessions' => $totalSessions,
                'total_energy_kwh' => round($totalKwh, 2),
                'total_cost' => round($totalCost, 0),
                'total_distance_km' => round($totalKm, 1),
                'kwh_per_100km' => round($kwhPer100km, 2),
                'cost_per_km' => round($costPerKm, 0),
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

        return $validated;
    }

    /**
     * Snapshot denormalized dari charging_stations saat sesi dibuat/diupdate.
     * Bila charging_station_id diberikan & station ditemukan, snapshot mengisi
     * station_*; jika tidak, snapshot dikosongkan (input manual tanpa link DB).
     */
    private function stationSnapshot(?int $stationId): array
    {
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
            'station_name_snapshot' => null,
            'station_address_snapshot' => null,
            'station_lat_snapshot' => null,
            'station_lng_snapshot' => null,
            'station_provider_snapshot' => null,
        ];
    }
}
