<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Application\Services\Nav\PlanNavScheduleService;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rule = $this->whenLoaded('activeRule', fn () => $this->activeRule);
        $configuration = $this->whenLoaded('configuration', fn () => $this->configuration);

        $navSchedule = null;

        if ($this->relationLoaded('configuration') && $this->configuration) {
            $navSchedule = app(PlanNavScheduleService::class)->getSchedule($this->resource);
        }

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

            'configuration' => $configuration ? [
                'id' => $configuration->id,
                'uuid' => $configuration->uuid,
                'plan_family' => $configuration->plan_family,
                'valuation_method' => $configuration->valuation_method,
                'initial_offer_start_date' => $configuration->initial_offer_start_date,
                'initial_offer_end_date' => $configuration->initial_offer_end_date,
                'initial_offer_duration_days' => $configuration->initial_offer_duration_days,
                'initial_offer_price_per_unit' => $configuration->initial_offer_price_per_unit,
                'initial_offer_price' => $configuration->initial_offer_price,
                'phase_status' => $configuration->phase_status,
                'live_phase_started_at' => optional($configuration->live_phase_started_at)?->toDateTimeString(),
                'market_close_time' => $configuration->market_close_time,
                'market_close_timezone' => $configuration->market_close_timezone,
                'auto_calculate_nav' => (bool) $configuration->auto_calculate_nav,
                'allow_nav_override' => (bool) $configuration->allow_nav_override,
                'allow_phase_override' => (bool) $configuration->allow_phase_override,
                'is_phase_overridden' => (bool) $configuration->is_phase_overridden,
                'phase_override_reason' => $configuration->phase_override_reason,
                'phase_override_by' => $configuration->phase_override_by,
                'phase_override_at' => optional($configuration->phase_override_at)?->toDateTimeString(),
                'total_units_on_offer' => $configuration->total_units_on_offer,
                'allow_post_offer_sales' => (bool) $configuration->allow_post_offer_sales,
                'unit_sale_cap_type' => $configuration->unit_sale_cap_type,
                'created_at' => optional($configuration->created_at)?->toDateTimeString(),
                'updated_at' => optional($configuration->updated_at)?->toDateTimeString(),
            ] : null,

            'nav_schedule' => $navSchedule,

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}