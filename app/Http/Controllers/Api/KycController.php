<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Kyc\KycCompletenessService;

class KycController extends Controller
{
    public function summary(
        Investor $investor,
        KycCompletenessService $kycCompletenessService
    ): JsonResponse {
        $summary = $kycCompletenessService->evaluate($investor);

        return response()->json([
            'message' => 'KYC summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function sync(
        Investor $investor,
        KycCompletenessService $kycCompletenessService
    ): JsonResponse {
        $summary = $kycCompletenessService->syncStatuses($investor);

        return response()->json([
            'message' => 'KYC statuses synchronized successfully.',
            'data' => $summary,
        ]);
    }
}