<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\KycReviewResource;
use App\Http\Requests\Kyc\StoreKycReviewRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Kyc\KycReviewService;

class KycReviewController extends Controller
{
    public function index(Investor $investor): JsonResponse
    {
        $reviews = $investor->kycReviews()->latest('id')->get();

        return response()->json([
            'message' => 'KYC reviews retrieved successfully.',
            'data' => KycReviewResource::collection($reviews),
        ]);
    }

    public function store(
        StoreKycReviewRequest $request,
        Investor $investor,
        KycReviewService $kycReviewService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $result = $kycReviewService->review(
            investor: $investor,
            reviewedBy: $request->user()->id,
            decision: $request->input('decision'),
            reviewNotes: $request->input('review_notes'),
            escalationReason: $request->input('escalation_reason'),
            overrideReason: $request->input('override_reason'),
        );

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor.kyc_reviewed',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'kyc_review_id' => $result['review']->id,
                'decision' => $result['review']->decision,
                'review_status' => $result['review']->review_status,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'KYC review recorded successfully.',
            'data' => [
                'review' => new KycReviewResource($result['review']),
                'investor' => [
                    'id' => $result['investor']->id,
                    'investor_number' => $result['investor']->investor_number,
                    'onboarding_status' => $result['investor']->onboarding_status,
                    'kyc_status' => $result['investor']->kyc_status,
                    'investor_status' => $result['investor']->investor_status,
                ],
                'kyc_summary' => $result['kyc_summary'],
            ],
        ]);
    }
}