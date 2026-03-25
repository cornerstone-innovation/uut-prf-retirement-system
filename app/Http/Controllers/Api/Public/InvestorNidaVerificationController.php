<?php

namespace App\Http\Controllers\Api\Public;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\VerifyNidaRequest;

class InvestorNidaVerificationController extends Controller
{
    public function verify(
        VerifyNidaRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'NIDA verify route is working.',
            'data' => [
                'session_uuid' => $request->input('session_uuid'),
                'nida_number' => $request->input('nida_number'),
            ],
        ]);
    }
}