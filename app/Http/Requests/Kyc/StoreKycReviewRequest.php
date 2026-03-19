<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKycReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected', 'escalated'])],
            'review_notes' => ['nullable', 'string'],
            'escalation_reason' => ['nullable', 'string'],
            'override_reason' => ['nullable', 'string'],
        ];
    }
}