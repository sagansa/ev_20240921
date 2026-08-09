<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class StateOfHealthResource extends JsonResource
{
    /**
     * Transform the resource into an array — kontrak eksplisit-array (preseden
     * ChargingSessionResource): tipe di-cast, relasi saat eager-loaded.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'battery_id' => $this->battery_id,
            'battery' => $this->whenLoaded('battery', function () {
                return new BatteryResource($this->battery);
            }),
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle?->id,
                    'license_plate' => $this->vehicle?->license_plate,
                ];
            }),
            'date' => $this->date ? Carbon::parse($this->date)->toDateString() : null,
            'km' => isset($this->km) ? (float) $this->km : null,
            'percentage' => isset($this->percentage) ? (float) $this->percentage : null,
            'remaining_battery' => isset($this->remaining_battery) ? (float) $this->remaining_battery : null,
            'image' => $this->image,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
