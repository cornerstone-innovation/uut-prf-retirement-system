<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Http\Requests\Purchase\StorePurchaseRequestRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Purchase\PurchaseRequestService;
use App\Application\Services\Purchase\PurchaseAllocationService;
use App\Http\Resources\InvestmentTransactionResource;
use App\Http\Resources\UnitLotResource;
use App\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $investor = $request->user()?->investor;

        if (! $investor) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an investor profile.',
            ], 422);
        }

        $requests = $investor->purchaseRequests()
            ->with('plan', 'latestPayment', 'investmentTransaction')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Purchase requests retrieved successfully.',
            'data' => PurchaseRequestResource::collection($requests),
        ]);
    }

    public function store(
        StorePurchaseRequestRequest $request,
        PurchaseRequestService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an investor profile.',
            ], 422);
        }

        $plan = Plan::with('activeRule')->findOrFail($request->integer('plan_id'));

        $purchaseRequest = $service->create(
            investor: $investor,
            plan: $plan,
            amount: (float) $request->input('amount'),
            option: $request->input('option'),
            requestType: $request->input('request_type', 'initial'),
            isSip: (bool) $request->boolean('is_sip', false),
            notes: $request->input('notes'),
            createdBy: $request->user()->id,
        );

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'purchase_request.created',
            entityType: 'purchase_request',
            entityId: $purchaseRequest->id,
            entityReference: $purchaseRequest->uuid,
            metadata: [
                'investor_id' => $investor->id,
                'plan_id' => $plan->id,
                'amount' => $purchaseRequest->amount,
                'request_type' => $purchaseRequest->request_type,
                'option' => $purchaseRequest->option,
                'status' => $purchaseRequest->status,
                'kyc_tier_at_request' => $purchaseRequest->kyc_tier_at_request,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Purchase request created successfully.',
            'data' => new PurchaseRequestResource($purchaseRequest->load('plan')),
        ], 201);
    }

    public function allocate(
        Request $request,
        PurchaseRequest $purchaseRequest,
        PurchaseAllocationService $allocationService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $result = $allocationService->allocate(
            purchaseRequest: $purchaseRequest,
            processedBy: $request->user()?->id,
        );

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'purchase_request.allocated',
            entityType: 'purchase_request',
            entityId: $purchaseRequest->id,
            entityReference: $purchaseRequest->uuid,
            metadata: [
                'investment_transaction_id' => $result['transaction']->id,
                'unit_lot_id' => $result['unit_lot']->id,
                'units' => $result['transaction']->units,
                'nav_per_unit' => $result['transaction']->nav_per_unit,
                'status' => $result['purchase_request']->status,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Purchase request allocated successfully.',
            'data' => [
                'purchase_request' => new PurchaseRequestResource($result['purchase_request']),
                'transaction' => new InvestmentTransactionResource($result['transaction']),
                'unit_lot' => new UnitLotResource($result['unit_lot']),
            ],
        ]);
    }
}