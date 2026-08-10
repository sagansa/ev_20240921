<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Review lokasi — ANONIM. Identitas reviewer (user_id) tidak pernah diekspos;
 * resource publik hanya membawa {id, rating, comment, created_at}.
 */
class StationReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->setTimezone('Asia/Jakarta')->toDateTimeString(),
        ];
    }
}
