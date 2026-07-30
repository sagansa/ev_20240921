<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'logo' => $this->logo,
            'contact' => $this->contact,
            'email' => $this->email,
            'web' => $this->web,
            'google' => $this->google,
            'ios' => $this->ios,
            'price' => $this->price,
            'admin_fee' => $this->admin_fee,
            'tax' => $this->tax,
            'location_count' => (int) ($this->spklu_locations_count ?? 0),
        ];
    }
}
