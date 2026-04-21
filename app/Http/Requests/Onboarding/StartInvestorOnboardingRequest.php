<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StartInvestorOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investor_type' => ['required', 'in:individual,corporate'],
            'phone_number' => ['required', 'string', 'max:30'],
            'nida_number' => ['nullable', 'string', 'max:100'],

            'document_type' => ['required', 'in:nida,passport,driving_licence'],
            'document_number' => ['required', 'string', 'max:100'],
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}