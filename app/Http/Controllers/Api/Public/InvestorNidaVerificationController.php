<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\InvestorOnboardingSession;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyNidaRequest;
use App\Application\Services\Onboarding\InvestorOnboardingService;
use App\Application\Services\Verification\YesIdVerificationService;

class InvestorNidaVerificationController extends Controller
{
    public function verify(
        VerifyNidaRequest $request,
        YesIdVerificationService $yesIdVerificationService,
        InvestorOnboardingService $onboardingService
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        $result = $yesIdVerificationService->verifyNida(
            session: $session,
            nidaNumber: $request->input('nida_number')
        );

        $session = $onboardingService->markNidaVerified(
            session: $session,
            prefillData: [
                'full_name' => $result['full_name'],
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'date_of_birth' => $result['date_of_birth'],
                'nationality' => $result['nationality'],
            ],
            nidaNumber: $request->input('nida_number')
        );

        return response()->json([
            'message' => 'NIDA verified successfully.',
            'data' => [
                'session_uuid' => $session->uuid,
                'nida_verified_at' => optional($session->nida_verified_at)?->toDateTimeString(),
                'prefill_data' => $session->prefill_data,
                'current_step' => $session->current_step,
                'provider_reference' => $result['provider_reference'],
            ],
        ]);
    }
}