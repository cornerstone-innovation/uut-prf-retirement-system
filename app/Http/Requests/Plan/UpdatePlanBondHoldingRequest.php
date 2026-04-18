<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanBondHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bond_name' => ['sometimes', 'string', 'max:255'],
            'bond_code' => ['nullable', 'string', 'max:100'],
            'principal_amount' => ['sometimes', 'numeric', 'gt:0'],
            'coupon_rate_percent' => ['sometimes', 'numeric', 'gte:0'],
            'issue_date' => ['nullable', 'date'],
            'investment_date' => ['sometimes', 'date'],
            'maturity_date' => ['nullable', 'date'],
            'coupon_frequency' => ['sometimes', 'string', 'max:50'],
            'last_coupon_date' => ['nullable', 'date'],
            'next_coupon_date' => ['nullable', 'date'],
            'accrued_interest_amount' => ['nullable', 'numeric', 'gte:0'],
            'face_value' => ['nullable', 'numeric', 'gte:0'],
            'holding_status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}