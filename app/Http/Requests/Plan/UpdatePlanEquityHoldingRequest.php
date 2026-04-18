<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanEquityHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_security_id' => ['sometimes', 'integer', 'exists:market_securities,id'],
            'quantity' => ['sometimes', 'numeric', 'gt:0'],
            'invested_amount' => ['sometimes', 'numeric', 'gte:0'],
            'average_cost_per_share' => ['nullable', 'numeric', 'gte:0'],
            'trade_date' => ['sometimes', 'date'],
            'holding_status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}