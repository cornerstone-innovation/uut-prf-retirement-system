<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartInvestorOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investor_type' => ['required', Rule::in(['individual', 'corporate'])],
            'phone_number' => ['required', 'string', 'max:30'],
            'nida_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}