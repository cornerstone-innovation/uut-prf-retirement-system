<?php

namespace App\Application\Services\Purchase;

use App\Models\Plan;
use App\Models\Investor;
use Illuminate\Support\Str;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Plan\PlanEligibilityService;

class PurchaseRequestService
{
    public function __construct(
        private readonly PlanEligibilityService $planEligibilityService,
        private readonly \App\Application\Services\Nav\CutoffTimeService $cutoffTimeService
    ) {
    }

    public function create(
        Investor $investor,
        Plan $plan,
        float $amount,
        string $option,
        string $requestType = 'initial',
        bool $isSip = false,
        ?string $notes = null,
        ?int $createdBy = null
    ): PurchaseRequest {
        $plan->loadMissing('activeRule');

        $isAdditionalInvestment = $requestType === 'additional';

        $this->ensureOptionAllowed($plan, $option);

        $eligibility = $this->planEligibilityService->ensureCanPurchase(
            investor: $investor,
            plan: $plan,
            amount: $amount,
            isAdditionalInvestment: $isAdditionalInvestment,
            isSip: $isSip
        );

        $pricing = $this->cutoffTimeService->resolvePricingDate($plan, now());

        return DB::transaction(function () use (
            $investor,
            $plan,
            $amount,
            $option,
            $requestType,
            $isSip,
            $notes,
            $createdBy,
            $eligibility,
            $pricing
        ) {
            return PurchaseRequest::create([
                'uuid' => (string) Str::uuid(),
                'investor_id' => $investor->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'currency' => 'TZS',
                'request_type' => $requestType,
                'option' => $option,
                'status' => 'pending_payment',
                'kyc_tier_at_request' => $eligibility['kyc_tier'] ?? null,
                'is_sip' => $isSip,
                'notes' => $notes,
                'metadata' => [
                    'plan_code' => $plan->code,
                    'plan_name' => $plan->name,
                    'cutoff_rule_id' => $pricing['cutoff_rule_id'] ?? null,
                    'cutoff_time' => $pricing['cutoff_time'] ?? null,
                    'timezone' => $pricing['timezone'] ?? null,
                ],
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'submitted_at' => now(),
                'pricing_date' => $pricing['pricing_date'] ?? null,
            ]);
        });
    }

    protected function ensureOptionAllowed(Plan $plan, string $option): void
    {
        $rule = $plan->activeRule;

        if (! $rule) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan has no active rule.'],
            ]);
        }

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