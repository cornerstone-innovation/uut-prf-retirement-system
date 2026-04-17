<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Plan\PlanUnitResolverService;

class PlanUnitSummaryController extends Controller
{
    public function show(Plan $plan, PlanUnitResolverService $unitResolverService): JsonResponse
    {
        return response()->json([
            'message' => 'Plan unit summary retrieved successfully.',
            'data' => $unitResolverService->getUnitSummary($plan),
        ]);
    }
}