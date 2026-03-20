<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rule = $this->whenLoaded('activeRule', fn () => $this->activeRule);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'fund_id' => $this->fund_id,
            'plan_category_id' => $this->plan_category_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_default' => $this->is_default,
            'investment_objective' => $this->investment_objective,
            'target_audience' => $this->target_audience,
            'metadata' => $this->metadata,

            'fund' => $this->whenLoaded('fund', function () {
                return [
                    'id' => $this->fund?->id,
                    'uuid' => $this->fund?->uuid,
                    'code' => $this->fund?->code,
                    'name' => $this->fund?->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category?->id,
                    'code' => $this->category?->code,
                    'name' => $this->category?->name,
                ];
            }),

            'active_rule' => $rule ? [
                'id' => $rule->id,
                'uuid' => $rule->uuid,
                'minimum_initial_investment' => $rule->minimum_initial_investment,
                'maximum_initial_investment' => $rule->maximum_initial_investment,
                'minimum_additional_investment' => $rule->minimum_additional_investment,
                'maximum_additional_investment' => $rule->maximum_additional_investment,
                'minimum_redemption_amount' => $rule->minimum_redemption_amount,
                'minimum_balance_after_redemption' => $rule->minimum_balance_after_redemption,
                'lock_in_period_years' => $rule->lock_in_period_years,
                'switching_allowed' => $rule->switching_allowed,
                'sip_allowed' => $rule->sip_allowed,
                'minimum_sip_amount' => $rule->minimum_sip_amount,
                'sip_frequency' => $rule->sip_frequency,
                'option_growth' => $rule->option_growth,
                'option_dividend' => $rule->option_dividend,
                'option_dividend_reinvestment' => $rule->option_dividend_reinvestment,
                'exit_fee_percentage' => $rule->exit_fee_percentage,
                'exit_fee_period_days' => $rule->exit_fee_period_days,
                'currency' => $rule->currency,
                'status' => $rule->status,
                'is_active' => $rule->is_active,
                'metadata' => $rule->metadata,
            ] : null,

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}