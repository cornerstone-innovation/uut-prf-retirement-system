<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanEquityHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_security_id' => ['required', 'integer', 'exists:market_securities,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'invested_amount' => ['required', 'numeric', 'gte:0'],
            'average_cost_per_share' => ['nullable', 'numeric', 'gte:0'],
            'trade_date' => ['required', 'date'],
            'holding_status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}