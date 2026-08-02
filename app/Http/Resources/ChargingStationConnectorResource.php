<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargingStationConnectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'nama_konektor' => $this->nama_konektor,
            'status_konektor' => $this->status_konektor,
            'status' => $this->status,
            'img_path' => $this->img_path,
            'status_updated_at' => $this->status_updated_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
        ];
    }
}
