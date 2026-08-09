<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BatteryResource;
use App\Models\Battery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * CRUD baterai + swap (pergantian baterai) untuk mobile.
 *
 * Swap = event life-cycle: menutup baterai aktif (removed_at/removed_km) dan
 * membuka baterai baru (installed_at/installed_km = km saat ini) secara
 * atomik. Tidak mengubah sesi charging / SoH historis — hanya menandai baterai
 * lama pensiun. Sesi & SoH berikutnya auto-attach ke baterai baru.
 */
class BatteryController extends Controller
{
    /**
     * List baterai milik user terautentikasi. Filter `vehicle_id`, opsional
     * `status` (0/1) atau `active=true` untuk hanya baterai aktif.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = $user->batteries()
            ->with(['vehicle'])
            ->orderByDesc('installed_at');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        } elseif ($request->boolean('active')) {
            $query->active();
        }

        $perPage = (int) ($request->per_page ?? 20);
        $perPage = max(1, min(100, $perPage));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Batteries retrieved successfully',
            'data' => BatteryResource::collection($paginator->getCollection()),
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
     * Tambah baterai baru (manual, tanpa swap). Status default aktif.
     * `capacity_kwh` otomatis diambil dari type_vehicle.battery_capacity bila kosong.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'label' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'capacity_kwh' => 'nullable|numeric|min:0',
            'installed_at' => 'required|date',
            'installed_km' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $user = Auth::user();
        $vehicle = $user->vehicles()->find($validated['vehicle_id']);
        if (! $vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        // Invariant: 1 kendaraan hanya boleh punya 1 baterai aktif. Bila sudah
        // ada, tolak — user wajib memakai endpoint swap (menutup yg lama +
        // membuka yg baru secara atomik), bukan create manual.
        if (Battery::activeForVehicle($validated['vehicle_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan sudah memiliki baterai aktif. Gunakan fitur ganti baterai (swap) untuk mengganti.',
            ], 422);
        }

        if (empty($validated['capacity_kwh']) && $vehicle->typeVehicle) {
            $validated['capacity_kwh'] = $vehicle->typeVehicle->battery_capacity;
        }

        $battery = $user->batteries()->create([
            'vehicle_id' => $validated['vehicle_id'],
            'label' => $validated['label'] ?? null,
            'serial_number' => $validated['serial_number'] ?? null,
            'capacity_kwh' => $validated['capacity_kwh'] ?? null,
            'installed_at' => $validated['installed_at'],
            'installed_km' => $validated['installed_km'] ?? null,
            'status' => 1,
            'note' => $validated['note'] ?? null,
        ]);

        $battery->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'Battery created successfully',
            'data' => new BatteryResource($battery),
        ], 201);
    }

    /**
     * Detail baterai.
     */
    public function show(Battery $battery): JsonResponse
    {
        if ((string) $battery->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to battery',
            ], 403);
        }

        $battery->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'Battery retrieved successfully',
            'data' => new BatteryResource($battery),
        ]);
    }

    /**
     * Update baterai.
     */
    public function update(Request $request, Battery $battery): JsonResponse
    {
        if ((string) $battery->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to battery',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'capacity_kwh' => 'nullable|numeric|min:0',
            'installed_at' => 'sometimes|date',
            'installed_km' => 'nullable|numeric|min:0',
            'removed_at' => 'nullable|date',
            'removed_km' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
            'note' => 'nullable|string',
        ]);

        $data = $request->only([
            'label',
            'serial_number',
            'capacity_kwh',
            'installed_at',
            'installed_km',
            'removed_at',
            'removed_km',
            'status',
            'note',
        ]);

        // Invariant: 1 kendaraan hanya boleh 1 baterai aktif. Cek hanya bila
        // update ini berpotensi mengaktifkan battery (status=1 atau
        // removed_at dikosongkan), dan battery ini sendiri sedang tidak aktif.
        $willBeActive = (isset($data['status']) ? (int) $data['status'] === 1 : true)
            && (array_key_exists('removed_at', $data) ? $data['removed_at'] === null : true);
        $currentlyInactive = $battery->status !== 1 || $battery->removed_at !== null;

        if ($willBeActive && $currentlyInactive) {
            $otherActive = Battery::query()
                ->where('vehicle_id', $battery->vehicle_id)
                ->where('id', '!=', $battery->id)
                ->active()
                ->exists();
            if ($otherActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kendaraan sudah memiliki baterai aktif lain. Tidak boleh ada dua baterai aktif sekaligus.',
                ], 422);
            }
        }

        $battery->update($data);

        $battery->load(['vehicle']);

        return response()->json([
            'success' => true,
            'message' => 'Battery updated successfully',
            'data' => new BatteryResource($battery),
        ]);
    }

    /**
     * Hapus baterai (soft delete).
     */
    public function destroy(Battery $battery): JsonResponse
    {
        if ((string) $battery->user_id !== (string) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to battery',
            ], 403);
        }

        $battery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Battery deleted successfully',
        ]);
    }

    /**
     * Pergantian baterai kendaraan — atomik:
     *  1. Lock & ambil baterai aktif kendaraan. Tidak ada → 422.
     *  2. Tutup baterai lama (removed_at = date, removed_km = km, status = 0).
     *  3. Buat baterai baru (installed_at = date, installed_km = km, status = 1).
     * Sesi charging & SoH historis TIDAK diubah.
     */
    public function swap(Request $request, string $vehicleId): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'km' => 'required|numeric|min:0',
            'new_label' => 'nullable|string|max:100',
            'new_serial_number' => 'nullable|string|max:100',
            'new_capacity_kwh' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $user = Auth::user();
        $vehicle = $user->vehicles()->find($vehicleId);
        if (! $vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $result = null;
        $error = null;

        try {
            DB::transaction(function () use ($user, $vehicleId, $validated, $vehicle, &$result) {
                $old = Battery::query()
                    ->where('vehicle_id', $vehicleId)
                    ->where('user_id', $user->id)
                    ->active()
                    ->orderByDesc('installed_at')
                    ->lockForUpdate()
                    ->first();

                if (! $old) {
                    throw new \RuntimeException('Tidak ada baterai aktif untuk kendaraan ini');
                }

                $old->update([
                    'removed_at' => $validated['date'],
                    'removed_km' => $validated['km'],
                    'status' => 0,
                ]);

                $capacity = $validated['new_capacity_kwh'] ?? null;
                if (empty($capacity) && $vehicle->typeVehicle) {
                    $capacity = $vehicle->typeVehicle->battery_capacity;
                }

                $new = $user->batteries()->create([
                    'vehicle_id' => $vehicleId,
                    'label' => $validated['new_label'] ?? null,
                    'serial_number' => $validated['new_serial_number'] ?? null,
                    'capacity_kwh' => $capacity,
                    'installed_at' => $validated['date'],
                    'installed_km' => $validated['km'],
                    'status' => 1,
                    'note' => $validated['note'] ?? null,
                ]);

                $old->load(['vehicle']);
                $new->load(['vehicle']);

                $result = [
                    'old' => new BatteryResource($old),
                    'new' => new BatteryResource($new),
                ];
            });
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Battery swapped successfully',
            'data' => $result,
        ]);
    }
}
