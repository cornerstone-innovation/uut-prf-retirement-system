<?php

namespace App\Http\Controllers\Api\Public;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyNidaRequest;
use App\Application\Services\Verification\YesIdVerificationService;
use App\Application\Services\Onboarding\InvestorOnboardingService;

class InvestorNidaVerificationController extends Controller
{
    public function verify(
        VerifyNidaRequest $request,
        InvestorOnboardingService $onboardingService,
        YesIdVerificationService $yesIdVerificationService
    ): JsonResponse {
        $session = $onboardingService->findActiveSessionByUuid(
            $request->string('session_id')->toString()
        );

        $result = $yesIdVerificationService->verifyNida(
            nidaNumber: $request->string('nida_number')->toString(),
            session: $session,
        );

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'NIDA verification failed.',
                'data' => $result,
            ], 422);
        }

        return response()->json([
            'message' => 'NIDA verified successfully.',
            'data' => [
                'session_id' => $session->uuid,
                'verified' => true,
                'full_name' => $result['full_name'] ?? null,
                'first_name' => $result['first_name'] ?? null,
                'last_name' => $result['last_name'] ?? null,
                'date_of_birth' => $result['date_of_birth'] ?? null,
                'gender' => $result['gender'] ?? null,
                'nationality' => $result['nationality'] ?? null,
                'reference' => $result['reference'] ?? null,
            ],
        ]);
    }
}