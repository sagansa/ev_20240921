<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializer baterai (Battery) — kontrak eksplisit-array (preseden
 * ChargingSessionResource): tipe field di-cast eksplisit, relasi hanya
 * serialize saat eager-loaded. Termasuk metrik turunan cycle_count & total_km
 * yang dihitung dari sesi charging milik baterai.
 */
class BatteryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'label' => $this->label,
            'serial_number' => $this->serial_number,
            'capacity_kwh' => isset($this->capacity_kwh) ? (float) $this->capacity_kwh : null,
            'installed_at' => $this->installed_at?->toDateString(),
            'installed_km' => isset($this->installed_km) ? (float) $this->installed_km : null,
            'removed_at' => $this->removed_at?->toDateString(),
            'removed_km' => isset($this->removed_km) ? (float) $this->removed_km : null,
            'status' => (int) $this->status,
            'is_active' => (int) $this->status === 1 && $this->removed_at === null,
            'note' => $this->note,

            // Metrik turunan dari sesi charging baterai.
            'cycle_count' => $this->cycle_count,
            'total_km' => $this->total_km,

            // Kendaraan (ringkas) — hanya saat eager-loaded.
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle?->id,
                    'license_plate' => $this->vehicle?->license_plate,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
