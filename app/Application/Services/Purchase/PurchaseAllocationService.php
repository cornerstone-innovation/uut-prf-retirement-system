<?php

namespace App\Application\Services\Purchase;

use App\Models\UnitLot;
use App\Models\PurchaseRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\InvestmentTransaction;
use App\Application\Services\Nav\NavRecordService;

class PurchaseAllocationService
{
    public function __construct(
        private readonly NavRecordService $navRecordService,
        private readonly \App\Application\Services\Plan\PlanUnitResolverService $planUnitResolverService
    ) {
    }
    public function allocate(
        PurchaseRequest $purchaseRequest,
        ?int $processedBy = null
    ): array {
        $purchaseRequest->loadMissing([
            'investor',
            'plan.activeRule',
            'latestPayment',
            'investmentTransaction',
        ]);

        if ($purchaseRequest->status === 'completed') {
            throw ValidationException::withMessages([
                'purchase_request' => ['This purchase request has already been completed.'],
            ]);
        }

        if ($purchaseRequest->status !== 'payment_received') {
            throw ValidationException::withMessages([
                'purchase_request' => ['Only purchase requests with received payment can be allocated.'],
            ]);
        }

        if ($purchaseRequest->investmentTransaction) {
            throw ValidationException::withMessages([
                'purchase_request' => ['This purchase request has already been allocated.'],
            ]);
        }

        $payment = $purchaseRequest->latestPayment;

        if (! $payment || $payment->status !== 'paid') {
            throw ValidationException::withMessages([
                'payment' => ['A paid payment record is required before allocation.'],
            ]);
        }

        $pricingDate = $purchaseRequest->pricing_date?->toDateString();

        if (! $pricingDate) {
            throw ValidationException::withMessages([
                'pricing_date' => ['Purchase request has no pricing date assigned.'],
            ]);
        }

        $navRecord = $this->navRecordService->getPublishedNavForDate(
            plan: $purchaseRequest->plan,
            valuationDate: $pricingDate
        );

        if (! $navRecord) {
            throw ValidationException::withMessages([
                'nav' => ["No published NAV exists for plan {$purchaseRequest->plan_id} on {$pricingDate}."],
            ]);
        }

        $nav = (float) $navRecord->nav_per_unit;

        if ($nav <= 0) {
            throw ValidationException::withMessages([
                'nav' => ['Applicable published NAV must be greater than zero for allocation.'],
            ]);
        }

        $grossAmount = (float) $purchaseRequest->amount;
        $netAmount = $grossAmount;
        $units = round($netAmount / $nav, 6);

        $this->planUnitResolverService->assertUnitsAvailable(
        plan: $purchaseRequest->plan,
        requestedUnits: $units
);

        return DB::transaction(function () use (
            $purchaseRequest,
            $grossAmount,
            $netAmount,
            $nav,
            $units,
            $processedBy,
            $pricingDate,
            $navRecord
        ) {
            $existingTransaction = InvestmentTransaction::query()
                ->where('purchase_request_id', $purchaseRequest->id)
                ->first();

            if ($existingTransaction) {
                throw ValidationException::withMessages([
                    'purchase_request' => ['This purchase request has already been allocated.'],
                ]);
            }

            $transaction = InvestmentTransaction::create([
                'uuid' => (string) Str::uuid(),
                'investor_id' => $purchaseRequest->investor_id,
                'plan_id' => $purchaseRequest->plan_id,
                'purchase_request_id' => $purchaseRequest->id,
                'transaction_type' => 'purchase',
                'status' => 'completed',
                'gross_amount' => $grossAmount,
                'net_amount' => $netAmount,
                'units' => $units,
                'nav_per_unit' => $nav,
                'currency' => $purchaseRequest->currency,
                'option' => $purchaseRequest->option,
                'trade_date' => now()->toDateString(),
                'pricing_date' => $pricingDate,
                'processed_at' => now(),
                'metadata' => [
                    'pricing_basis' => 'published_nav',
                    'nav_record_id' => $navRecord->id,
                    'nav_record_uuid' => $navRecord->uuid,
                    'payment_id' => $purchaseRequest->latestPayment?->id,
                ],
                'created_by' => $processedBy,
                'updated_by' => $processedBy,
            ]);

            $lot = UnitLot::create([
                'uuid' => (string) Str::uuid(),
                'investor_id' => $purchaseRequest->investor_id,
                'plan_id' => $purchaseRequest->plan_id,
                'investment_transaction_id' => $transaction->id,
                'original_units' => $units,
                'remaining_units' => $units,
                'nav_per_unit' => $nav,
                'gross_amount' => $grossAmount,
                'acquired_date' => $pricingDate,
                'status' => 'active',
                'metadata' => [
                    'purchase_request_id' => $purchaseRequest->id,
                    'option' => $purchaseRequest->option,
                    'nav_record_id' => $navRecord->id,
                ],
            ]);

            $purchaseRequest->update([
                'status' => 'completed',
                'updated_by' => $processedBy,
            ]);

            return [
                'purchase_request' => $purchaseRequest->fresh(['plan', 'latestPayment', 'investmentTransaction']),
                'transaction' => $transaction->fresh(),
                'unit_lot' => $lot->fresh(),
            ];
        });
    }
}