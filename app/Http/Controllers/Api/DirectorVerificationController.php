<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CompanyDirector;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Verification\DirectorVerificationService;

class DirectorVerificationController extends Controller
{
    public function verifyIdentity(
        CompanyDirector $companyDirector,
        DirectorVerificationService $directorVerificationService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $result = $directorVerificationService->verifyNationalId($companyDirector);

        $auditLogger->log(
            userId: auth()->id(),
            action: 'company_director.identity_verification_triggered',
            entityType: 'company_director',
            entityId: $result['director']->id,
            entityReference: $result['director']->full_name,
            metadata: [
                'verification_id' => $result['verification']->id,
                'verification_status' => $result['verification']->status,
                'provider' => $result['verification']->provider,
                'verification_type' => $result['verification']->verification_type,
            ],
            request: request()
        );

        return response()->json([
            'message' => 'Director identity verification processed successfully.',
            'data' => [
                'director_id' => $result['director']->id,
                'director_name' => $result['director']->full_name,
                'identity_verification_status' => $result['director']->identity_verification_status,
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