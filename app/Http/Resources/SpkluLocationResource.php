<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkluLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) ($this->external_id ?? $this->id),
            'provinsi' => $this->provinsi,
            'kabupaten_kota' => $this->kabupaten_kota,
            'nama_lokasi' => $this->nama_lokasi,
            'alamat' => $this->alamat,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'keterangan' => $this->keterangan,
            'status' => (int) ($this->status ?? 1),
            'type_charge' => $this->type_charge,
            'watt' => $this->watt,
            'total_charger' => (int) $this->total_charger,
            'total_konektor' => (int) $this->total_konektor,
            // Status real-time agregat (fold dari konektor ESDM oleh poller)
            'availability_level' => $this->availability_level,
            'available_count' => (int) $this->available_count,
            'charging_count' => (int) $this->charging_count,
            'finishing_count' => (int) $this->finishing_count,
            'status_updated_at' => $this->status_updated_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
            'distance_km' => $this->when(isset($this->distance) && $this->distance !== null, fn () => round((float) $this->distance, 4)),
            'provider_id' => $this->provider_id,
            'provider' => $this->when($this->relationLoaded('provider') && $this->provider, function () {
                return [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'logo' => $this->provider->logo,
                    'contact' => $this->provider->contact,
                    'web' => $this->provider->web,
                    'google' => $this->provider->google,
                    'ios' => $this->provider->ios,
                ];
            }),
            'provider_name' => $this->provider_name ?? $this->provider?->name ?? 'PLN Mobile',
            'provider_logo' => $this->provider?->logo ?? null,
            'charger_boxes' => SpkluChargerBoxResource::collection($this->whenLoaded('chargerBoxes')),
        ];
    }
}
