<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class VerifyNidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_uuid' => ['required', 'uuid', 'exists:investor_onboarding_sessions,uuid'],
            'nida_number' => ['required', 'string', 'max:100'],
        ];
    }
}