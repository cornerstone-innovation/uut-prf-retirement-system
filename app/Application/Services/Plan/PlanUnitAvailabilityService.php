<?php

namespace App\Application\Services\Plan;

use App\Models\Plan;
use Illuminate\Validation\ValidationException;

class PlanUnitAvailabilityService
{
    public function __construct(
        private readonly PlanUnitResolverService $planUnitResolverService
    ) {
    }

    public function resolveEstimatedUnits(
        Plan $plan,
        float $amount,
        ?float $price = null
    ): array {
        $configuration = $plan->configuration;

        if (! $configuration) {
            throw ValidationException::withMessages([
                'plan' => ['Plan configuration is missing.'],
            ]);
        }

        $resolvedPrice = $price;

        if ($resolvedPrice === null) {
            if (($configuration->phase_status ?? null) === 'initial_offer') {
                $resolvedPrice = (float) ($configuration->initial_offer_price ?? 0);
            } else {
                $latestValuation = $plan->valuationSnapshots()
                    ->latest('valuation_date')
                    ->latest('id')
                    ->first();

                $resolvedPrice = (float) ($latestValuation?->nav_per_unit ?? 0);
            }
        }

        if ($resolvedPrice <= 0) {
            throw ValidationException::withMessages([
                'price' => ['Unable to determine a valid unit price for this plan.'],
            ]);
        }

        $estimatedUnits = round($amount / $resolvedPrice, 6);
        $remainingUnits = $this->planUnitResolverService->getRemainingUnitsForSale($plan);

        return [
            'unit_price_used' => round($resolvedPrice, 6),
            'estimated_units' => $estimatedUnits,
            'remaining_units_for_sale' => round($remainingUnits, 6),
            'can_allocate' => $estimatedUnits <= $remainingUnits,
        ];
    }

    public function ensureCanAllocateEstimatedUnits(
        Plan $plan,
        float $amount,
        ?float $price = null
    ): array {
        $result = $this->resolveEstimatedUnits($plan, $amount, $price);

        if (! $result['can_allocate']) {
            throw ValidationException::withMessages([
                'units' => [
                    "Requested investment would allocate {$result['estimated_units']} units, but only {$result['remaining_units_for_sale']} units remain available for sale.",
                ],
            ]);
        }

        return $result;
    }
}