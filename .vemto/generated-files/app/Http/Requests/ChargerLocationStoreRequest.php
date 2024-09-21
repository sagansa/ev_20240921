<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargerLocationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'max:1024'],
            'name' => ['required', 'string'],
            'provider_id' => ['required'],
            'location_on' => ['required'],
            'status' => ['required'],
            'description' => ['nullable', 'string'],
            'latitude' => ['required'],
            'longitude' => ['required'],
            'parking' => ['required', 'boolean'],
            'province_id' => ['required'],
            'city_id' => ['required'],
            'district_id' => ['nullable'],
            'subdistrict_id' => ['nullable'],
            'postal_code_id' => ['nullable'],
            'user_id' => ['required'],
        ];
    }
}
