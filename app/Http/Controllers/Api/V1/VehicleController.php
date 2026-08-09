<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\BrandVehicle;
use App\Models\Charge;
use App\Models\ModelVehicle;
use App\Models\TypeVehicle;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * Faktor loss charging (%) konsisten dgn `ChargingCostCalculator` mobile
     * (estimateKwhFromBattery: lossFactor = 1.1). HARUS cocok dgn mobile —
     * bila diubah di salah satu sisi, hasil estimasi tidak sinkron lagi.
     */
    protected const LOSS_FACTOR = 1.1;

    /**
     * Display a listing of the user's vehicles.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = $user->vehicles()
            ->with([
                'brandVehicle',
                'modelVehicle',
                'typeVehicle',
            ]);

        $vehicles = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Vehicles retrieved successfully',
            'data' => $vehicles,
        ]);
    }

    /**
     * Master dropdown options for mobile forms (brands, models, types).
     */
    public function options(): JsonResponse
    {
        $brands = BrandVehicle::select('id', 'name', 'image')->orderBy('name')->get();
        $models = ModelVehicle::select('id', 'brand_vehicle_id', 'name', 'image')->orderBy('name')->get();
        $types = TypeVehicle::select('id', 'model_vehicle_id', 'name', 'battery_capacity')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'brands' => $brands,
                'models' => $models,
                'types' => $types,
            ],
        ]);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_vehicle_id' => 'nullable|exists:brand_vehicles,id',
            'brand_id' => 'nullable|exists:brand_vehicles,id',
            'model_vehicle_id' => 'nullable|exists:model_vehicles,id',
            'model_id' => 'nullable|exists:model_vehicles,id',
            'type_vehicle_id' => 'nullable|exists:type_vehicles,id',
            'type_id' => 'nullable|exists:type_vehicles,id',
            'license_plate' => 'required|string|max:20',
            'battery_capacity_kwh' => 'nullable|numeric|min:0|max:300',
            'ownership' => 'nullable|date',
            'status' => 'nullable|integer',
            'image' => 'nullable|string',
        ]);

        $brandId = $validated['brand_vehicle_id'] ?? $validated['brand_id'] ?? null;
        $modelId = $validated['model_vehicle_id'] ?? $validated['model_id'] ?? null;
        $typeId = $validated['type_vehicle_id'] ?? $validated['type_id'] ?? null;

        if (! $brandId || ! $modelId) {
            return response()->json([
                'success' => false,
                'message' => 'brand_vehicle_id and model_vehicle_id are required',
            ], 422);
        }

        $user = Auth::user();

        $vehicle = $user->vehicles()->create([
            'brand_vehicle_id' => $brandId,
            'model_vehicle_id' => $modelId,
            'type_vehicle_id' => $typeId,
            'license_plate' => $validated['license_plate'],
            'battery_capacity_kwh' => $validated['battery_capacity_kwh'] ?? null,
            'ownership' => $validated['ownership'] ?? null,
            'status' => $validated['status'] ?? 1,
            'image' => $validated['image'] ?? null,
        ]);

        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => $vehicle,
        ], 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $user = Auth::user();

        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle retrieved successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $user = Auth::user();

        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $validated = $request->validate([
            'brand_vehicle_id' => 'nullable|exists:brand_vehicles,id',
            'brand_id' => 'nullable|exists:brand_vehicles,id',
            'model_vehicle_id' => 'nullable|exists:model_vehicles,id',
            'model_id' => 'nullable|exists:model_vehicles,id',
            'type_vehicle_id' => 'nullable|exists:type_vehicles,id',
            'type_id' => 'nullable|exists:type_vehicles,id',
            'license_plate' => 'nullable|string|max:20',
            'battery_capacity_kwh' => 'nullable|numeric|min:0|max:300',
            'ownership' => 'nullable|date',
            'status' => 'nullable|integer',
            'image' => 'nullable|string',
        ]);

        $data = [];
        if (isset($validated['brand_vehicle_id']) || isset($validated['brand_id'])) {
            $data['brand_vehicle_id'] = $validated['brand_vehicle_id'] ?? $validated['brand_id'];
        }
        if (isset($validated['model_vehicle_id']) || isset($validated['model_id'])) {
            $data['model_vehicle_id'] = $validated['model_vehicle_id'] ?? $validated['model_id'];
        }
        if (array_key_exists('type_vehicle_id', $validated) || array_key_exists('type_id', $validated)) {
            $data['type_vehicle_id'] = $validated['type_vehicle_id'] ?? $validated['type_id'];
        }
        if (array_key_exists('license_plate', $validated)) {
            $data['license_plate'] = $validated['license_plate'];
        }
        if (array_key_exists('battery_capacity_kwh', $validated)) {
            $data['battery_capacity_kwh'] = $validated['battery_capacity_kwh'];
        }
        if (array_key_exists('ownership', $validated)) {
            $data['ownership'] = $validated['ownership'];
        }
        if (isset($validated['status'])) {
            $data['status'] = $validated['status'];
        }
        if (array_key_exists('image', $validated)) {
            $data['image'] = $validated['image'];
        }

        $oldRawCapacity = $vehicle->getRawOriginal('battery_capacity_kwh');

        $vehicle->update($data);

        // Kapasitas berubah → hitung ulang semua sesi estimasi (is_kwh_measured
        // = false) milik kendaraan ini. Sesi terukur & sesi publik (dari struk)
        // tidak disentuh.
        $recalculatedSessions = 0;
        $capacityChanged = array_key_exists('battery_capacity_kwh', $validated)
            && (float) ($validated['battery_capacity_kwh'] ?? 0) !== (float) ($oldRawCapacity ?? 0);
        if ($capacityChanged) {
            $newCapacity = $data['battery_capacity_kwh'] ?? $vehicle->typeVehicle?->battery_capacity;
            if ($newCapacity !== null) {
                $recalculatedSessions = $this->recalcEstimateSessions($vehicle, (float) $newCapacity);
            }
        }

        $vehicle->load([
            'brandVehicle',
            'modelVehicle',
            'typeVehicle',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle,
            'recalculated_sessions' => $recalculatedSessions,
        ]);
    }

    /**
     * Hitung ulang kWh + total_cost sesi ESTIMASI (is_kwh_measured = false)
     * milik kendaraan saat battery capacity dikoreksi.
     *
     * Rumus kWh konsisten dgn mobile: (finish − start) / 100 × capacity × LOSS_FACTOR.
     * Cost:
     *  - Sesi HOME (charging_station_id null): total_cost di-scale proporsional
     *    (tarif PLN lama tidak tersimpan per sesi; rincian pajak tidak bisa
     *    direkonstruksi — trade-off eksplisit yang disepakati).
     *  - Sesi PUBLIK (charging_station_id terisi, dari struk): cost TIDAK disentuh.
     *
     * @return int Jumlah sesi yang diperbarui.
     */
    public function recalcEstimateSessions(Vehicle $vehicle, float $newCapacity): int
    {
        $charges = Charge::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_kwh_measured', false)
            ->whereNotNull('start_charging_now')
            ->whereNotNull('finish_charging_now')
            ->get();

        $count = 0;
        foreach ($charges as $charge) {
            $oldKwh = (float) ($charge->kWh ?? 0);
            $start = (float) $charge->start_charging_now;
            $finish = (float) $charge->finish_charging_now;
            $newKwh = max(0.0, ($finish - $start) / 100.0 * $newCapacity * self::LOSS_FACTOR);

            $charge->kWh = $newKwh;

            // Home scaling: hanya sesi tanpa SPKLU publik & punya cost lama.
            if ($charge->charging_station_id === null && $oldKwh > 0 && (float) ($charge->total_cost ?? 0) > 0) {
                $oldCost = (float) $charge->total_cost;
                $charge->total_cost = (int) round($oldCost * $newKwh / $oldKwh);
            }

            $charge->save();
            $count++;
        }

        return $count;
    }

    /**
     * Remove the specified vehicle from storage (soft delete).
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $user = Auth::user();

        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to vehicle',
            ], 403);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }
}