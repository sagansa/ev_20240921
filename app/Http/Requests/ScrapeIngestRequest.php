<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScrapeIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:36'],
            'places' => ['required', 'array', 'max:500'],
            'places.*.place_id' => ['nullable', 'string', 'max:255'],
            'places.*.nama_lokasi' => ['required', 'string', 'max:255'],
            'places.*.alamat' => ['nullable', 'string', 'max:500'],
            'places.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'places.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'places.*.rating' => ['nullable', 'numeric', 'between:0,5'],
            'places.*.total_reviews' => ['nullable', 'integer', 'min:0'],
            'places.*.phone' => ['nullable', 'string', 'max:50'],
            'places.*.opening_hours' => ['nullable', 'string', 'max:255'],
            'places.*.website' => ['nullable', 'url', 'max:255'],
            'places.*.provider_name' => ['nullable', 'string', 'max:100'],
            'places.*.type_charge' => ['nullable', 'string', 'max:50'],
            'places.*.max_kw' => ['nullable', 'integer', 'min:0'],
            'places.*.total_charger' => ['nullable', 'integer', 'min:0'],
            'places.*.total_konektor' => ['nullable', 'integer', 'min:0'],
            'places.*.chargers' => ['nullable', 'array'],
            'places.*.chargers.*.connector_type' => ['nullable', 'string', 'max:50'],
            'places.*.chargers.*.power_kw' => ['nullable', 'integer', 'min:0'],
            'places.*.chargers.*.type_charge' => ['nullable', 'string', 'max:50'],
            'places.*.chargers.*.jumlah_charger' => ['nullable', 'integer', 'min:0'],
            'places.*.chargers.*.jumlah_konektor' => ['nullable', 'string', 'max:10'],
        ];
    }
}
