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
        ];
    }
}
