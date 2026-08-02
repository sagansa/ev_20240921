<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkluChargerBoxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'chargerbox_id' => $this->chargerbox_id,
            'type_charge' => $this->type_charge,
            'nama_chargerbox' => $this->nama_chargerbox,
            'watt' => $this->watt,
            'jumlah_charger' => (int) $this->jumlah_charger,
            'jumlah_konektor' => (string) ($this->jumlah_konektor ?? '1'),
            'icon' => $this->icon,
            'gambar' => $this->gambar,
            // Status real-time per charger box (fold dari konektor ESDM oleh poller)
            'availability_level' => $this->availability_level,
            'available_count' => (int) $this->available_count,
            'charging_count' => (int) $this->charging_count,
            'finishing_count' => (int) $this->finishing_count,
            'status_updated_at' => $this->status_updated_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
            // Plug individual (paling granular) — status real-time per-konektor
            'connectors' => ChargingStationConnectorResource::collection($this->whenLoaded('connectors')),
        ];
    }
}

