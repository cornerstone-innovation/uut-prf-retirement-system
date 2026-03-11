<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyDirectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'national_id_number' => ['nullable', 'string', 'max:255'],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'has_signing_authority' => ['required', 'boolean'],
            'ownership_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}