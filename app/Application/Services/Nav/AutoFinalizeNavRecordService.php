<?php

namespace App\Application\Services\Nav;

use App\Models\NavRecord;
use App\Application\Services\Purchase\AutoAllocationService;
use Illuminate\Support\Facades\DB;

class AutoFinalizeNavRecordService
{
    public function __construct(
        private readonly AutoAllocationService $autoAllocationService,
    ) {
    }

    public function finalize(
        NavRecord $navRecord,
        ?int $systemUserId = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($navRecord, $systemUserId, $notes) {
            $navRecord->update([
                'approved_by_1' => $systemUserId,
                'approved_at_1' => now(),
                'approved_by_2' => $systemUserId,
                'approved_at_2' => now(),
                'published_by' => $systemUserId,
                'published_at' => now(),
                'status' => 'published',
                'notes' => $notes ?: $navRecord->notes,
            ]);

            $allocationSummary = $this->autoAllocationService->allocateForPublishedNav(
                navRecord: $navRecord->fresh(),
                processedBy: $systemUserId,
            );

            return [
                'nav_record' => $navRecord->fresh(),
                'auto_allocation' => $allocationSummary,
            ];
        });
    }
}