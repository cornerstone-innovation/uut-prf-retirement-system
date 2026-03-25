<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteInvestorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_uuid' => ['required', 'uuid', 'exists:investor_onboarding_sessions,uuid'],

            'investor_type' => ['required', Rule::in(['individual', 'corporate'])],
            'full_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'tax_identification_number' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'source_of_funds' => ['nullable', 'string'],
            'risk_profile' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],

            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_primary' => ['required', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'alternate_contact_name' => ['nullable', 'string', 'max:255'],
            'alternate_contact_phone' => ['nullable', 'string', 'max:30'],
            'preferred_contact_method' => ['nullable', Rule::in(['email', 'phone', 'sms', 'whatsapp'])],

            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address_type' => ['required', Rule::in(['residential', 'postal', 'business'])],
            'addresses.*.country' => ['required', 'string', 'max:100'],
            'addresses.*.region' => ['nullable', 'string', 'max:100'],
            'addresses.*.city' => ['nullable', 'string', 'max:100'],
            'addresses.*.district' => ['nullable', 'string', 'max:100'],
            'addresses.*.ward' => ['nullable', 'string', 'max:100'],
            'addresses.*.street' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_address' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:50'],
            'addresses.*.is_primary' => ['required', 'boolean'],

            'nominees' => ['nullable', 'array'],
            'nominees.*.full_name' => ['required_with:nominees', 'string', 'max:255'],
            'nominees.*.relationship' => ['required_with:nominees', 'string', 'max:100'],
            'nominees.*.date_of_birth' => ['nullable', 'date'],
            'nominees.*.phone' => ['nullable', 'string', 'max:30'],
            'nominees.*.email' => ['nullable', 'email', 'max:255'],
            'nominees.*.national_id_number' => ['nullable', 'string', 'max:100'],
            'nominees.*.allocation_percentage' => ['required_with:nominees', 'numeric', 'min:0', 'max:100'],
            'nominees.*.is_minor' => ['required_with:nominees', 'boolean'],
            'nominees.*.guardian_name' => ['nullable', 'string', 'max:255'],
            'nominees.*.guardian_phone' => ['nullable', 'string', 'max:30'],
            'nominees.*.address' => ['nullable', 'string'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}