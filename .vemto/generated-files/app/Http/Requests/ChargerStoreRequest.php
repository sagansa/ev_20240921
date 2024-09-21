<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargerStoreRequest extends FormRequest
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
            'charger_location_id' => ['required'],
            'current_charger_id' => ['required'],
            'type_charger_id' => ['required'],
            'power_charger_id' => ['required'],
            'unit' => ['required'],
        ];
    }
}
