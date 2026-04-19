<?php

namespace App\Application\Services\Purchase;

use App\Models\Plan;
use App\Models\Investor;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Nav\NavRecordService;
use App\Application\Services\Plan\PlanEligibilityService;
use App\Application\Services\Nav\CutoffTimeService;
use App\Application\Services\Plan\PlanUnitAvailabilityService;

class PurchasePreviewService
{
        public function __construct(
            private readonly PlanEligibilityService $planEligibilityService,
            private readonly NavRecordService $navRecordService,
            private readonly CutoffTimeService $cutoffTimeService,
            private readonly PlanUnitAvailabilityService $planUnitAvailabilityService
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
        try {
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

            $pricing = $this->cutoffTimeService->resolvePricingDate($plan, now());

            if (! is_array($pricing)) {
                throw ValidationException::withMessages([
                    'cutoff' => ['Failed to resolve pricing date. Cutoff configuration may be missing.'],
                ]);
            }

            $pricingDate = $pricing['pricing_date'] ?? null;

            if (! $pricingDate) {
                throw ValidationException::withMessages([
                    'cutoff' => ['Pricing date could not be determined.'],
                ]);
            }

            $navRecord = $this->navRecordService->getPublishedNavForDate(
                plan: $plan,
                valuationDate: $pricingDate
            );

            if (! $navRecord) {
                throw ValidationException::withMessages([
                    'nav' => ["No published NAV exists for this plan on {$pricingDate}."],
                ]);
            }

            if (! isset($navRecord->nav_per_unit)) {
                throw ValidationException::withMessages([
                    'nav' => ['NAV record is invalid or missing value.'],
                ]);
            }

            $nav = (float) $navRecord->nav_per_unit;

            if ($nav <= 0) {
                throw ValidationException::withMessages([
                    'nav' => ['Applicable published NAV must be greater than zero.'],
                ]);
            }

            $entryLoadAmount = 0.00;
            $netInvestableAmount = round($amount - $entryLoadAmount, 2);

            if ($netInvestableAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Net investable amount must be greater than zero.'],
                ]);
            }


            $plan->loadMissing('configuration');

            $unitAvailability = $this->planUnitAvailabilityService
                ->ensureCanAllocateEstimatedUnits(
                    plan: $plan,
                    amount: $netInvestableAmount,
                    price: $nav // IMPORTANT: force NAV used in preview
                );

            $estimatedUnits = $unitAvailability['estimated_units'];

           

            $canPayNow = (bool) ($pricing['can_pay_now'] ?? false);
            $requiresReconfirmation = (bool) ($pricing['requires_reconfirmation'] ?? false);
            $submittedAfterCutoff = (bool) ($pricing['submitted_after_cutoff'] ?? false);

            return [

            'unit_availability' => [
            'unit_price_used' => $unitAvailability['unit_price_used'],
            'estimated_units' => $unitAvailability['estimated_units'],
            'remaining_units_for_sale' => $unitAvailability['remaining_units_for_sale'],
            'can_allocate' => $unitAvailability['can_allocate'],
        ],
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
                'cutoff_rule_id' => $pricing['cutoff_rule_id'] ?? null,
                'cutoff_time' => $pricing['cutoff_time'] ?? null,
                'timezone' => $pricing['timezone'] ?? null,
                'submitted_at_local' => $pricing['submitted_at_local'] ?? null,
                'submitted_after_cutoff' => $submittedAfterCutoff,
                'can_pay_now' => $canPayNow,
                'requires_reconfirmation' => $requiresReconfirmation,
                'next_action' => $canPayNow ? 'checkout' : 'wait_for_nav',
                'investor_notice' => $canPayNow
                    ? 'You are within the active cutoff time and may proceed to payment.'
                    : 'Your request was submitted after the cutoff time. The applicable NAV may change in the next dealing cycle. Your request can be saved as pending confirmation until the next active NAV is available.',
            ];
        } catch (\Throwable $e) {
            Log::error('Purchase preview failed.', [
                'investor_id' => $investor->id ?? null,
                'plan_id' => $plan->id ?? null,
                'amount' => $amount,
                'option' => $option,
                'request_type' => $requestType,
                'is_sip' => $isSip,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
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