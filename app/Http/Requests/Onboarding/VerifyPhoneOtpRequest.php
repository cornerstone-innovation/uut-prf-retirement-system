<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneOtpRequest extends FormRequest
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
            'otp_code' => ['required', 'digits_between:4,10'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_uuid.required' => 'Onboarding session is required.',
            'session_uuid.uuid' => 'Session UUID is invalid.',
            'session_uuid.exists' => 'Onboarding session was not found.',
            'phone_number.required' => 'Phone number is required.',
            'otp_code.required' => 'OTP code is required.',
            'otp_code.digits_between' => 'OTP code format is invalid.',
        ];
    }
}