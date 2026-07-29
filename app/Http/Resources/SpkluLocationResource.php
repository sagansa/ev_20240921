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
            'status' => (int) $this->status,
            'type_charge' => $this->type_charge,
            'watt' => $this->watt,
            'total_charger' => (int) $this->total_charger,
            'total_konektor' => (int) $this->total_konektor,
            'distance_km' => $this->when(isset($this->distance), round($this->distance, 4)),
            'provider_id' => $this->provider_id,
            'provider' => $this->when($this->relationLoaded('provider') && $this->provider, function () {
                return [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'logo' => $this->provider->logo,
                    'contact' => $this->provider->contact,
                ];
            }),
            'provider_name' => $this->provider?->name ?? 'PLN Mobile',
            'provider_logo' => $this->provider?->logo ?? null,
            'charger_boxes' => SpkluChargerBoxResource::collection($this->whenLoaded('chargerBoxes')),
        ];
    }
}
