<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\NavRecord;
use App\Models\PlanValuationSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateAndCreateNavRecordService
{
    public function __construct(
        private readonly BuildPlanNavSnapshotDataService $buildPlanNavSnapshotDataService,
        private readonly CalculatePlanNavService $calculatePlanNavService,
        private readonly CreateNavRecordFromCalculationService $createNavRecordFromCalculationService,
    ) {
    }

    public function execute(
        Plan $plan,
        string $valuationDate,
        ?int $createdBy = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($plan, $valuationDate, $createdBy, $notes) {
            $snapshot = $this->calculatePlanNavService->calculateAndStore(
                plan: $plan,
                valuationDate: $valuationDate,
                createdBy: $createdBy,
            );

            $existingNavRecord = NavRecord::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->first();

            if (! $existingNavRecord) {
                $navRecord = $this->createNavRecordFromCalculationService->create(
                    plan: $plan,
                    calculationData: [
                        'valuation_date' => $snapshot->valuation_date->toDateString(),
                        'nav_per_unit' => (float) $snapshot->nav_per_unit,
                        'net_asset_value' => (float) $snapshot->net_asset_value,
                        'outstanding_units' => (float) $snapshot->outstanding_units,
                        'price_source' => $snapshot->price_source,
                        'breakdown' => $snapshot->breakdown ?? [],
                    ],
                    createdBy: $createdBy,
                    notes: $notes ?? 'Auto-created from NAV calculation.',
                );
            } else {
                $navRecord = $existingNavRecord;
            }

            return [
                'snapshot' => $snapshot,
                'nav_record' => $navRecord,
            ];
        });
    }
}