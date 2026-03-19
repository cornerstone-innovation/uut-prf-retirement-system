<?php

namespace App\Application\Services\Plan;

use App\Models\Plan;
use App\Models\Investor;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Kyc\KycCompletenessService;

class PlanEligibilityService
{
    public function __construct(
        private readonly KycCompletenessService $kycCompletenessService
    ) {
    }

    public function getAccessSummary(Investor $investor, ?Plan $plan = null): array
    {
        $kyc = $this->kycCompletenessService->evaluate($investor);

        return [
            'investor_id' => $investor->id,
            'investor_number' => $investor->investor_number,
            'kyc_tier' => $kyc['kyc_tier'],
            'identity_verified' => $kyc['identity_verified'],
            'profile_completed' => $kyc['profile_completed'],
            'documents_completed' => $kyc['documents_completed'],
            'can_view_products' => $kyc['can_view_products'],
            'can_purchase' => $kyc['can_purchase'],
            'can_redeem' => $kyc['can_redeem'],
            'plan' => $plan ? [
                'id' => $plan->id,
                'code' => $plan->code,
                'name' => $plan->name,
                'status' => $plan->status,
            ] : null,
        ];
    }

    public function ensureCanViewProducts(Investor $investor): void
    {
        $kyc = $this->kycCompletenessService->evaluate($investor);

        if (! $kyc['can_view_products']) {
            throw ValidationException::withMessages([
                'access' => ['Investor is not yet eligible to view products.'],
            ]);
        }
    }

    public function ensureCanPurchase(
        Investor $investor,
        Plan $plan,
        float $amount,
        bool $isAdditionalInvestment = false,
        bool $isSip = false
    ): array {
        $kyc = $this->kycCompletenessService->evaluate($investor);

        if (! $kyc['can_purchase']) {
            throw ValidationException::withMessages([
                'access' => ['Investor is not yet eligible to purchase.'],
            ]);
        }

        if (! in_array($plan->status, ['approved', 'active'], true)) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan is not available for investment.'],
            ]);
        }

        $rule = $plan->activeRule;

        if (! $rule) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan has no active investment rule.'],
            ]);
        }

        $minimum = $isAdditionalInvestment
            ? (float) ($rule->minimum_additional_investment ?? 0)
            : (float) ($rule->minimum_initial_investment ?? 0);

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => [
                    $isAdditionalInvestment
                        ? "Additional investment amount must be at least {$minimum}."
                        : "Initial investment amount must be at least {$minimum}."
                ],
            ]);
        }

        if ($isSip) {
            if (! $rule->sip_allowed) {
                throw ValidationException::withMessages([
                    'sip' => ['This plan does not allow SIP investments.'],
                ]);
            }

            $minimumSip = (float) ($rule->minimum_sip_amount ?? 0);

            if ($amount < $minimumSip) {
                throw ValidationException::withMessages([
                    'amount' => ["SIP amount must be at least {$minimumSip}."],
                ]);
            }
        }

        return [
            'eligible' => true,
            'investor_id' => $investor->id,
            'plan_id' => $plan->id,
            'kyc_tier' => $kyc['kyc_tier'],
            'can_purchase' => $kyc['can_purchase'],
            'minimum_required_amount' => $minimum,
            'sip_allowed' => (bool) $rule->sip_allowed,
            'minimum_sip_amount' => $rule->minimum_sip_amount,
        ];
    }

    public function ensureCanRedeem(Investor $investor, Plan $plan): array
    {
        $kyc = $this->kycCompletenessService->evaluate($investor);

        if (! $kyc['can_redeem']) {
            throw ValidationException::withMessages([
                'access' => ['Investor is not yet eligible to redeem.'],
            ]);
        }

        return [
            'eligible' => true,
            'investor_id' => $investor->id,
            'plan_id' => $plan->id,
            'kyc_tier' => $kyc['kyc_tier'],
            'can_redeem' => $kyc['can_redeem'],
        ];
    }
}