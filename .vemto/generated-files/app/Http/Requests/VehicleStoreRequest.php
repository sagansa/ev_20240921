<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleStoreRequest extends FormRequest
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
            'brand_vehicle_id' => ['required'],
            'model_vehicle_id' => ['required'],
            'type_vehicle_id' => ['nullable'],
            'license_plate' => ['nullable', 'string'],
            'ownership' => ['nullable', 'date'],
            'status' => ['required'],
        ];
    }
}
