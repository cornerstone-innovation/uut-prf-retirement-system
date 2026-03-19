<?php

namespace App\Application\Services\Purchase;

use App\Models\Plan;
use App\Models\Investor;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Nav\NavRecordService;
use App\Application\Services\Plan\PlanEligibilityService;

class PurchasePreviewService
{
    public function __construct(
        private readonly PlanEligibilityService $planEligibilityService,
        private readonly NavRecordService $navRecordService,
        private readonly \App\Application\Services\Nav\CutoffTimeService $cutoffTimeService
    ) {
    }

    public function preview(
        Investor $investor,
        Plan $plan,
        float $amount,
        string $option,
        string $requestType = 'initial',
        bool $isSip = false
    ): array {
        $plan->loadMissing('activeRule', 'category');

        $isAdditionalInvestment = $requestType === 'additional';

        $eligibility = $this->planEligibilityService->ensureCanPurchase(
            investor: $investor,
            plan: $plan,
            amount: $amount,
            isAdditionalInvestment: $isAdditionalInvestment,
            isSip: $isSip
        );

        $rule = $plan->activeRule;

        if (! $rule) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan has no active rule.'],
            ]);
        }

        $this->ensureOptionAllowed($rule, $option);

        /*
        |--------------------------------------------------------------------------
        | Pricing date
        |--------------------------------------------------------------------------
        | For now, use today's date.
        | Later, this will be driven by cutoff-time rules.
        |--------------------------------------------------------------------------
        */
        $pricing = $this->cutoffTimeService->resolvePricingDate($plan, now());
        $pricingDate = $pricing['pricing_date'];

        $navRecord = $this->navRecordService->getPublishedNavForDate(
            plan: $plan,
            valuationDate: $pricingDate
        );

        if (! $navRecord) {
            throw ValidationException::withMessages([
                'nav' => ["No published NAV exists for plan {$plan->id} on {$pricingDate}."],
            ]);
        }

        $nav = (float) $navRecord->nav_per_unit;

        if ($nav <= 0) {
            throw ValidationException::withMessages([
                'nav' => ['Applicable published NAV must be greater than zero.'],
            ]);
        }

        // Entry load placeholder:
        // can be expanded later into actual charge rules
        $entryLoadAmount = 0.00;

        $netInvestableAmount = round($amount - $entryLoadAmount, 2);

        if ($netInvestableAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Net investable amount must be greater than zero.'],
            ]);
        }

        $estimatedUnits = round($netInvestableAmount / $nav, 6);

        return [
            'investor_id' => $investor->id,
            'investor_number' => $investor->investor_number,
            'plan_id' => $plan->id,
            'plan_code' => $plan->code,
            'plan_name' => $plan->name,
            'plan_category' => [
                'id' => $plan->category?->id,
                'code' => $plan->category?->code,
                'name' => $plan->category?->name,
            ],
            'request_type' => $requestType,
            'is_sip' => $isSip,
            'option' => $option,
            'currency' => 'TZS',
            'amount' => round($amount, 2),
            'indicative_nav' => round($nav, 4),
            'entry_load_amount' => round($entryLoadAmount, 2),
            'net_investable_amount' => $netInvestableAmount,
            'estimated_units' => $estimatedUnits,
            'kyc_tier' => $eligibility['kyc_tier'],
            'can_purchase' => $eligibility['can_purchase'],
            'minimum_required_amount' => $eligibility['minimum_required_amount'],
            'pricing_date' => $pricingDate,
            'pricing_basis' => 'published_nav',
            'nav_record_id' => $navRecord->id,
            'nav_record_uuid' => $navRecord->uuid,
            'message' => 'Final units will be allocated at the applicable dealing-day published NAV.',
            'cutoff_rule_id' => $pricing['cutoff_rule_id'],
            'cutoff_time' => $pricing['cutoff_time'],
            'timezone' => $pricing['timezone'],
            'submitted_at_local' => $pricing['submitted_at_local'],
        ];
    }

    protected function ensureOptionAllowed(object $rule, string $option): void
    {
        $allowed = match ($option) {
            'growth' => (bool) $rule->option_growth,
            'dividend' => (bool) $rule->option_dividend,
            'dividend_reinvestment' => (bool) $rule->option_dividend_reinvestment,
            default => false,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'option' => ['Selected option is not allowed for this plan.'],
            ]);
        }
    }
}