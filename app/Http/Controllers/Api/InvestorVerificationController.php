<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Verification\InvestorVerificationService;

class InvestorVerificationController extends Controller
{
    public function verifyIdentity(
        Investor $investor,
        InvestorVerificationService $investorVerificationService,
        AuditLogger $auditLogger
    ): JsonResponse {
        if ($investor->investor_type !== 'individual') {
            return response()->json([
                'message' => 'Investor identity verification is currently only supported for individual investors.',
            ], 422);
        }

        $result = $investorVerificationService->verifyNationalId($investor);

        $auditLogger->log(
            userId: auth()->id(),
            action: 'investor.identity_verification_triggered',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'verification_id' => $result['verification']->id,
                'verification_status' => $result['verification']->status,
                'provider' => $result['verification']->provider,
                'verification_type' => $result['verification']->verification_type,
            ],
            request: request()
        );

        return response()->json([
            'message' => 'Investor identity verification processed successfully.',
            'data' => [
                'investor_id' => $result['investor']->id,
                'investor_number' => $result['investor']->investor_number,
                'kyc_profile_identity_status' => $result['investor']->kycProfile?->identity_verification_status,
                'verification' => [
                    'id' => $result['verification']->id,
                    'uuid' => $result['verification']->uuid,
                    'provider' => $result['verification']->provider,
                    'verification_type' => $result['verification']->verification_type,
                    'status' => $result['verification']->status,
                    'provider_reference' => $result['verification']->provider_reference,
                    'score' => $result['verification']->score,
                    'failure_reason' => $result['verification']->failure_reason,
                    'verified_at' => optional($result['verification']->verified_at)?->toDateTimeString(),
                ],
                'kyc_summary' => $result['kyc_summary'],
            ],
        ]);
    }
}