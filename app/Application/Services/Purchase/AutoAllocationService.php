<?php

namespace App\Application\Services\Purchase;

use App\Models\NavRecord;
use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;

class AutoAllocationService
{
    public function __construct(
        private readonly PurchaseAllocationService $purchaseAllocationService
    ) {
    }

    public function allocateForPublishedNav(
        NavRecord $navRecord,
        ?int $processedBy = null
    ): array {
        $purchaseRequests = PurchaseRequest::query()
            ->where('plan_id', $navRecord->plan_id)
            ->whereDate('pricing_date', $navRecord->valuation_date)
            ->where('status', 'payment_received')
            ->with(['plan', 'latestPayment'])
            ->orderBy('id')
            ->get();

        $allocated = [];
        $failed = [];

        foreach ($purchaseRequests as $purchaseRequest) {
            try {
                $result = $this->purchaseAllocationService->allocate(
                    purchaseRequest: $purchaseRequest,
                    processedBy: $processedBy
                );

                $allocated[] = [
                    'purchase_request_id' => $purchaseRequest->id,
                    'purchase_request_uuid' => $purchaseRequest->uuid,
                    'transaction_id' => $result['transaction']->id,
                    'unit_lot_id' => $result['unit_lot']->id,
                    'units' => $result['transaction']->units,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'purchase_request_id' => $purchaseRequest->id,
                    'purchase_request_uuid' => $purchaseRequest->uuid,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'nav_record_id' => $navRecord->id,
            'plan_id' => $navRecord->plan_id,
            'valuation_date' => $navRecord->valuation_date?->toDateString(),
            'matched_requests' => $purchaseRequests->count(),
            'allocated_count' => count($allocated),
            'failed_count' => count($failed),
            'allocated' => $allocated,
            'failed' => $failed,
        ];
    }
}