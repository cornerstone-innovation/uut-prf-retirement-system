<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class SendPhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_uuid' => ['required', 'uuid', 'exists:investor_onboarding_sessions,uuid'],
            'phone_number' => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_uuid.required' => 'Onboarding session is required.',
            'session_uuid.uuid' => 'Session UUID is invalid.',
            'session_uuid.exists' => 'Onboarding session was not found.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.max' => 'Phone number is too long.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone_number')) {
            $this->merge([
                'phone_number' => trim((string) $this->input('phone_number')),
            ]);
        }

        if ($this->has('session_uuid')) {
            $this->merge([
                'session_uuid' => trim((string) $this->input('session_uuid')),
            ]);
        }
    }
}