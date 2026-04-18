<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use Illuminate\Support\Str;
use App\Models\PlanValuationSnapshot;
use Illuminate\Validation\ValidationException;

class CalculatePlanNavService
{
    public function calculateAndStore(Plan $plan, string $valuationDate, ?int $createdBy = null): PlanValuationSnapshot
    {
        $configuration = $plan->configuration;

        if (! $configuration) {
            throw ValidationException::withMessages([
                'plan' => ['Plan configuration is missing.'],
            ]);
        }

        $planFamily = $configuration->plan_family;

        $result = match ($planFamily) {
            'youngsters' => app(YoungstersNavCalculatorService::class)->calculate($plan, $valuationDate),
            default => throw ValidationException::withMessages([
                'plan_family' => ["NAV calculation is not yet implemented for plan family: {$planFamily}."],
            ]),
        };

        $existingUuid = PlanValuationSnapshot::query()
            ->where('plan_id', $plan->id)
            ->whereDate('valuation_date', $valuationDate)
            ->value('uuid');

        return PlanValuationSnapshot::query()->updateOrCreate(
            [
                'plan_id' => $plan->id,
                'valuation_date' => $valuationDate,
            ],
            [
                'uuid' => $existingUuid ?: (string) Str::uuid(),
                'plan_family' => $result['plan_family'],
                'equity_market_value' => $result['equity_market_value'],
                'bond_market_value' => $result['bond_market_value'],
                'bond_accrued_interest' => $result['bond_accrued_interest'],
                'cash_value' => $result['cash_value'],
                'total_gross_asset_value' => $result['total_gross_asset_value'],
                'total_liabilities' => $result['total_liabilities'],
                'net_asset_value' => $result['net_asset_value'],
                'outstanding_units' => $result['outstanding_units'],
                'nav_per_unit' => $result['nav_per_unit'],
                'price_source' => $result['price_source'],
                'calculation_status' => $result['calculation_status'],
                'breakdown' => $result['breakdown'],
                'created_by' => $createdBy,
            ]
        );
    }
}