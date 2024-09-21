<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeStoreRequest extends FormRequest
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
            'vehicle_id' => ['required'],
            'date' => ['required', 'date'],
            'charger_location_id' => ['required'],
            'charger_id' => ['required'],
            'km_now' => ['required'],
            'km_before' => ['required'],
            'start_charging_now' => ['required'],
            'finish_charging_now' => ['required'],
            'finish_charging_before' => ['required'],
            'parking' => ['required'],
            'kWh' => ['required'],
            'street_lighting_tax' => ['required'],
            'value_added_tax' => ['required'],
            'admin_cost' => ['required'],
            'total_cost' => ['required'],
            'image' => ['nullable', 'image', 'max:1024'],
        ];
    }
}
