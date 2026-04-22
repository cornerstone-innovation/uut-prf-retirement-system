<?php

namespace App\Application\Services\Plan;

use App\Models\Plan;
use App\Models\InvestmentTransaction;
use Illuminate\Validation\ValidationException;

class PlanUnitResolverService
{
    public function getIssuedUnits(Plan $plan): float
    {
        return round(
            (float) InvestmentTransaction::query()
                ->where('plan_id', $plan->id)
                ->where('transaction_type', 'purchase')
                ->where('status', 'completed')
                ->sum('units'),
            6
        );
    }

    public function getRedeemedUnits(Plan $plan): float
    {
        return round(
            (float) InvestmentTransaction::query()
                ->where('plan_id', $plan->id)
                ->where('transaction_type', 'redemption')
                ->where('status', 'completed')
                ->sum('units'),
            6
        );
    }

    public function getOutstandingUnits(Plan $plan): float
    {
        $issuedUnits = $this->getIssuedUnits($plan);
        $redeemedUnits = $this->getRedeemedUnits($plan);

        return round(max(0, $issuedUnits - $redeemedUnits), 6);
    }

    public function getTotalUnitsOnOffer(Plan $plan): float
    {
        $plan->loadMissing('configuration');

        $config = $plan->configuration;

        if (! $config || $config->total_units_on_offer === null) {
            throw ValidationException::withMessages([
                'plan' => ['Total units on offer is not configured for this plan.'],
            ]);
        }

        return round((float) $config->total_units_on_offer, 6);
    }

    public function getRemainingUnitsForSale(Plan $plan): float
    {
        $totalUnitsOnOffer = $this->getTotalUnitsOnOffer($plan);
        $issuedUnits = $this->getIssuedUnits($plan);

        return round(max(0, $totalUnitsOnOffer - $issuedUnits), 6);
    }

    public function assertUnitsAvailable(Plan $plan, float $requestedUnits): void
    {
        $plan->loadMissing('configuration');

        $config = $plan->configuration;

        if (! $config) {
            throw ValidationException::withMessages([
                'plan' => ['Plan configuration is missing.'],
            ]);
        }

        $capType = $config->unit_sale_cap_type ?? 'fixed_units';

        if ($capType !== 'fixed_units') {
            return;
        }

        $remainingUnits = $this->getRemainingUnitsForSale($plan);

        if ($requestedUnits > $remainingUnits) {
            throw ValidationException::withMessages([
                'units' => [
                    "Requested units ({$requestedUnits}) exceed remaining available units ({$remainingUnits}).",
                ],
            ]);
        }
    }

    public function getUnitSummary(Plan $plan): array
    {
        $totalUnitsOnOffer = $this->getTotalUnitsOnOffer($plan);
        $issuedUnits = $this->getIssuedUnits($plan);
        $redeemedUnits = $this->getRedeemedUnits($plan);
        $outstandingUnits = $this->getOutstandingUnits($plan);
        $remainingUnitsForSale = $this->getRemainingUnitsForSale($plan);

        return [
            'total_units_on_offer' => $totalUnitsOnOffer,
            'issued_units' => $issuedUnits,
            'redeemed_units' => $redeemedUnits,
            'outstanding_units' => $outstandingUnits,
            'remaining_units_for_sale' => $remainingUnitsForSale,
        ];
    }
}