<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanBondHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bond_name' => ['required', 'string', 'max:255'],
            'bond_code' => ['nullable', 'string', 'max:100'],
            'principal_amount' => ['required', 'numeric', 'gt:0'],
            'coupon_rate_percent' => ['required', 'numeric', 'gte:0'],
            'issue_date' => ['nullable', 'date'],
            'investment_date' => ['required', 'date'],
            'maturity_date' => ['nullable', 'date'],
            'coupon_frequency' => ['required', 'string', 'max:50'],
            'last_coupon_date' => ['nullable', 'date'],
            'next_coupon_date' => ['nullable', 'date'],
            'accrued_interest_amount' => ['nullable', 'numeric', 'gte:0'],
            'face_value' => ['nullable', 'numeric', 'gte:0'],
            'holding_status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}