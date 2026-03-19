<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class CheckPurchaseEligibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'is_additional_investment' => ['nullable', 'boolean'],
            'is_sip' => ['nullable', 'boolean'],
        ];
    }
}