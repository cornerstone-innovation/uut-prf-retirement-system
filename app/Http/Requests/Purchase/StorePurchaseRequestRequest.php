<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'option' => ['required', Rule::in(['growth', 'dividend', 'dividend_reinvestment'])],
            'request_type' => ['nullable', Rule::in(['initial', 'additional', 'sip'])],
            'is_sip' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}