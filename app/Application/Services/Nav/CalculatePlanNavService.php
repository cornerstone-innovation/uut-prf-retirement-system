<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\PlanValuationSnapshot;
use Illuminate\Support\Str;

class CalculatePlanNavService
{
    public function __construct(
        private readonly BuildPlanNavSnapshotDataService $buildPlanNavSnapshotDataService
    ) {
    }

    public function calculateAndStore(
        Plan $plan,
        string $valuationDate,
        ?int $createdBy = null
    ): PlanValuationSnapshot {
        $data = $this->buildPlanNavSnapshotDataService->build(
            plan: $plan,
            valuationDate: $valuationDate
        );

        return PlanValuationSnapshot::updateOrCreate(
            [
                'plan_id' => $plan->id,
                'valuation_date' => $data['valuation_date'],
            ],
            [
                'uuid' => (string) Str::uuid(),
                'plan_family' => $data['plan_family'],
                'equity_market_value' => $data['equity_market_value'],
                'bond_market_value' => $data['bond_market_value'],
                'bond_accrued_interest' => $data['bond_accrued_interest'],
                'cash_value' => $data['cash_value'],
                'total_gross_asset_value' => $data['total_gross_asset_value'],
                'total_liabilities' => $data['total_liabilities'],
                'net_asset_value' => $data['net_asset_value'],
                'outstanding_units' => $data['outstanding_units'],
                'nav_per_unit' => $data['nav_per_unit'],
                'price_source' => $data['price_source'],
                'calculation_status' => $data['calculation_status'],
                'breakdown' => $data['breakdown'],
                'created_by' => $createdBy,
            ]
        );
    }
}