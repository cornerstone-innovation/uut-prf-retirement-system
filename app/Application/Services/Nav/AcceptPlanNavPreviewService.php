<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\NavRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptPlanNavPreviewService
{
    public function __construct(
        private readonly BuildPlanNavSnapshotDataService $buildPlanNavSnapshotDataService
    ) {
    }

    public function accept(
        Plan $plan,
        string $valuationDate,
        ?string $notes = null,
        ?int $createdBy = null
    ): NavRecord {
        $existing = NavRecord::query()
            ->where('plan_id', $plan->id)
            ->whereDate('valuation_date', $valuationDate)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'valuation_date' => [
                    "A NAV record already exists for plan {$plan->id} on {$valuationDate}.",
                ],
            ]);
        }

        $snapshot = $this->buildPlanNavSnapshotDataService->build($plan, $valuationDate);

        return DB::transaction(function () use ($plan, $valuationDate, $notes, $createdBy, $snapshot) {
            return NavRecord::create([
                'uuid' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'valuation_date' => $valuationDate,
                'nav_per_unit' => $snapshot['nav_per_unit'],
                'status' => 'pending_approval',
                'source' => 'calculation_preview',
                'notes' => $notes,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);
        });
    }
}