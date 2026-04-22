<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\NavRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RunAutomaticPlanNavService
{
    public function __construct(
        private readonly CalculatePlanNavService $calculatePlanNavService,
        private readonly CreateNavRecordFromCalculationService $createNavRecordFromCalculationService,
        private readonly AutoFinalizeNavRecordService $autoFinalizeNavRecordService,
    ) {
    }

    public function run(
        Plan $plan,
        string $valuationDate,
        ?int $systemUserId = null,
    ): array {
        $plan->loadMissing('configuration');

        if (! $plan->configuration) {
            throw ValidationException::withMessages([
                'plan' => ['Plan configuration is missing.'],
            ]);
        }

        if (! (bool) $plan->configuration->auto_calculate_nav) {
            throw ValidationException::withMessages([
                'plan' => ['Automatic NAV calculation is disabled for this plan.'],
            ]);
        }

        if (($plan->configuration->phase_status ?? null) !== 'live_nav') {
            throw ValidationException::withMessages([
                'plan' => ['Automatic NAV calculation only runs in Live NAV phase.'],
            ]);
        }

        return DB::transaction(function () use ($plan, $valuationDate, $systemUserId) {
            $existingPublished = NavRecord::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->where('status', 'published')
                ->first();

            if ($existingPublished) {
                return [
                    'snapshot' => null,
                    'nav_record' => $existingPublished,
                    'nav_record_created' => false,
                    'already_published' => true,
                    'auto_allocation' => null,
                ];
            }

            $snapshot = $this->calculatePlanNavService->calculateAndStore(
                plan: $plan,
                valuationDate: $valuationDate,
                createdBy: $systemUserId,
            );

            $existingRecord = NavRecord::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->first();

            if (! $existingRecord) {
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
                    createdBy: $systemUserId,
                    notes: 'Auto-created from scheduled NAV calculation.',
                );

                $navRecordCreated = true;
            } else {
                $navRecord = $existingRecord;
                $navRecordCreated = false;
            }

            if ($navRecord->status !== 'published') {
                $finalized = $this->autoFinalizeNavRecordService->finalize(
                    navRecord: $navRecord,
                    systemUserId: $systemUserId,
                    notes: 'Automatically approved and published by scheduled NAV engine.',
                );
            } else {
                $finalized = [
                    'nav_record' => $navRecord,
                    'auto_allocation' => null,
                ];
            }

            return [
                'snapshot' => $snapshot,
                'nav_record' => $finalized['nav_record'],
                'nav_record_created' => $navRecordCreated,
                'already_published' => false,
                'auto_allocation' => $finalized['auto_allocation'],
            ];
        });
    }
}