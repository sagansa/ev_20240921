<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogbookEntryResource extends JsonResource
{
    /**
     * Transform the logbook entry into the mobile contract.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'charging_station_id' => $this->charging_station_id,
            'station_name' => $this->station_name,
            'station_address' => $this->station_address,
            'station_latitude' => $this->station_latitude,
            'station_longitude' => $this->station_longitude,
            'station_provider' => $this->station_provider,
            'station_type_charge' => $this->station_type_charge,
            'session_at' => $this->session_at?->toIso8601String(),
            'odometer_km' => $this->odometer_km,
            'distance_driven_km' => $this->distance_driven_km,
            'energy_kwh' => $this->energy_kwh,
            'total_cost' => $this->total_cost,
            'parking_cost' => $this->parking_cost,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
