<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Foto lokasi — ANONIM. Identitas uploader (user_id) tidak pernah diekspos;
 * resource publik hanya membawa {id, url, created_at}.
 */
class StationPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'url' => $this->url(),
            'created_at' => $this->created_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
        ];
    }
}
