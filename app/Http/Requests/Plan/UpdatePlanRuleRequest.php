<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'minimum_initial_investment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximum_initial_investment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimum_additional_investment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximum_additional_investment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimum_redemption_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimum_balance_after_redemption' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'lock_in_period_years' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'switching_allowed' => ['sometimes', 'boolean'],
            'sip_allowed' => ['sometimes', 'boolean'],
            'minimum_sip_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sip_frequency' => ['sometimes', 'nullable', 'string', 'max:50'],

            'option_growth' => ['sometimes', 'boolean'],
            'option_dividend' => ['sometimes', 'boolean'],
            'option_dividend_reinvestment' => ['sometimes', 'boolean'],

            'exit_fee_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'exit_fee_period_days' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'currency' => ['sometimes', 'nullable', 'string', 'max:10'],
            'status' => ['sometimes', 'nullable', 'in:draft,active,inactive'],
            'is_active' => ['sometimes', 'boolean'],

            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}