<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\InvestorOnboardingSession;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyNidaRequest;

class InvestorNidaVerificationController extends Controller
{
    public function verify(
        VerifyNidaRequest $request
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        return response()->json([
            'message' => 'NIDA session lookup is working.',
            'data' => [
                'session_id' => $session->id,
                'session_uuid' => $session->uuid,
                'investor_type' => $session->investor_type,
                'phone_number' => $session->phone_number,
                'nida_number' => $request->input('nida_number'),
            ],
        ]);
    }
}