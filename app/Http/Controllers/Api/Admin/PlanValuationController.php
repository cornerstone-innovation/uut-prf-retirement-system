<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Nav\CalculatePlanNavService;

class PlanValuationController extends Controller
{
    public function calculate(
        Request $request,
        Plan $plan,
        CalculatePlanNavService $calculatePlanNavService
    ): JsonResponse {
        $validated = $request->validate([
            'valuation_date' => ['required', 'date'],
        ]);

        $snapshot = $calculatePlanNavService->calculateAndStore(
            plan: $plan,
            valuationDate: $validated['valuation_date'],
            createdBy: $request->user()?->id,
        );

        return response()->json([
            'message' => 'Plan NAV calculated successfully.',
            'data' => $snapshot,
        ]);
    }

    public function index(Plan $plan): JsonResponse
    {
        $rows = $plan->valuationSnapshots()
            ->latest('valuation_date')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Plan valuation snapshots retrieved successfully.',
            'data' => $rows,
        ]);
    }

    public function latest(Plan $plan): JsonResponse
    {
        $row = $plan->valuationSnapshots()
            ->latest('valuation_date')
            ->latest('id')
            ->first();

        return response()->json([
            'message' => 'Latest plan valuation snapshot retrieved successfully.',
            'data' => $row,
        ]);
    }
}