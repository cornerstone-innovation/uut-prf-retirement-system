<?php

namespace App\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];
    }
}