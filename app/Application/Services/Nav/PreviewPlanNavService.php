<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;

class PreviewPlanNavService
{
    public function __construct(
        private readonly BuildPlanNavSnapshotDataService $buildPlanNavSnapshotDataService
    ) {
    }

    public function preview(Plan $plan, string $valuationDate): array
    {
        return $this->buildPlanNavSnapshotDataService->build(
            plan: $plan,
            valuationDate: $valuationDate
        );
    }
}