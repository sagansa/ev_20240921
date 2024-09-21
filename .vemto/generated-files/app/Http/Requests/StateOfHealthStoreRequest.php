<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StateOfHealthStoreRequest extends FormRequest
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
            'vehicle_id' => ['required'],
            'km' => ['required'],
            'percentage' => ['required'],
            'remaining_battery' => ['nullable'],
            'user_id' => ['required'],
        ];
    }
}
