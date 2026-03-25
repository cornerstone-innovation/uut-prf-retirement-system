<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\InvestorOnboardingSession;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyNidaRequest;
use App\Application\Services\Verification\YesIdVerificationService;

class InvestorNidaVerificationController extends Controller
{
    public function verify(
        VerifyNidaRequest $request,
        YesIdVerificationService $yesIdVerificationService
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        $result = $yesIdVerificationService->verifyNida(
            session: $session,
            nidaNumber: $request->input('nida_number')
        );

        return response()->json([
            'message' => 'NIDA service call is working.',
            'data' => [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'provider_result' => $result,
            ],
        ]);
    }
}