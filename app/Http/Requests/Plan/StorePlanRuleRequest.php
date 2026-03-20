<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'minimum_initial_investment' => ['nullable', 'numeric', 'min:0'],
            'maximum_initial_investment' => ['nullable', 'numeric', 'min:0'],
            'minimum_additional_investment' => ['nullable', 'numeric', 'min:0'],
            'maximum_additional_investment' => ['nullable', 'numeric', 'min:0'],
            'minimum_redemption_amount' => ['nullable', 'numeric', 'min:0'],
            'minimum_balance_after_redemption' => ['nullable', 'numeric', 'min:0'],

            'lock_in_period_years' => ['nullable', 'integer', 'min:0'],

            'switching_allowed' => ['nullable', 'boolean'],
            'sip_allowed' => ['nullable', 'boolean'],
            'minimum_sip_amount' => ['nullable', 'numeric', 'min:0'],
            'sip_frequency' => ['nullable', 'string', 'max:50'],

            'option_growth' => ['nullable', 'boolean'],
            'option_dividend' => ['nullable', 'boolean'],
            'option_dividend_reinvestment' => ['nullable', 'boolean'],

            'exit_fee_percentage' => ['nullable', 'numeric', 'min:0'],
            'exit_fee_period_days' => ['nullable', 'integer', 'min:0'],

            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'in:draft,active,inactive'],
            'is_active' => ['nullable', 'boolean'],

            'metadata' => ['nullable', 'array'],
        ];
    }
}