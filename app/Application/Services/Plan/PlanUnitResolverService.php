<?php

namespace App\Application\Services\Plan;

use App\Models\Plan;
use App\Models\InvestmentTransaction;
use Illuminate\Validation\ValidationException;

class PlanUnitResolverService
{
    public function getIssuedUnits(Plan $plan): float
    {
        return (float) InvestmentTransaction::query()
            ->where('plan_id', $plan->id)
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->sum('units');
    }

    public function getTotalUnitsOnOffer(Plan $plan): float
    {
        $config = $plan->configuration;

        if (! $config || $config->total_units_on_offer === null) {
            throw ValidationException::withMessages([
                'plan' => ['Total units on offer is not configured for this plan.'],
            ]);
        }

        return (float) $config->total_units_on_offer;
    }

    public function getRemainingUnitsForSale(Plan $plan): float
    {
        $totalUnits = $this->getTotalUnitsOnOffer($plan);
        $issuedUnits = $this->getIssuedUnits($plan);

        return max(0, $totalUnits - $issuedUnits);
    }

    public function assertUnitsAvailable(Plan $plan, float $requestedUnits): void
    {
        $config = $plan->configuration;

        if (! $config) {
            throw ValidationException::withMessages([
                'plan' => ['Plan configuration is missing.'],
            ]);
        }

        if (($config->unit_sale_cap_type ?? 'fixed_cap') !== 'fixed_cap') {
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
        $totalUnits = $this->getTotalUnitsOnOffer($plan);
        $issuedUnits = $this->getIssuedUnits($plan);
        $remainingUnits = max(0, $totalUnits - $issuedUnits);

        return [
            'total_units_on_offer' => round($totalUnits, 6),
            'issued_units' => round($issuedUnits, 6),
            'remaining_units_for_sale' => round($remainingUnits, 6),
        ];
    }
}