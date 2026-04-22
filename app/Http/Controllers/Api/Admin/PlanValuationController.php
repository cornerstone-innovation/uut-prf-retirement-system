<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Nav\PreviewPlanNavService;
use App\Application\Services\Nav\CalculatePlanNavService;
use App\Application\Services\Nav\CreateNavRecordFromCalculationService;

class PlanValuationController extends Controller
{
    public function preview(
        Request $request,
        Plan $plan,
        PreviewPlanNavService $previewPlanNavService
    ): JsonResponse {
        $validated = $request->validate([
            'valuation_date' => ['required', 'date'],
        ]);

        $preview = $previewPlanNavService->preview(
            plan: $plan,
            valuationDate: $validated['valuation_date'],
        );

        return response()->json([
            'message' => 'Plan NAV preview generated successfully.',
            'data' => $preview,
        ]);
    }

    public function calculate(
        Request $request,
        Plan $plan,
        CalculatePlanNavService $calculatePlanNavService,
        CreateNavRecordFromCalculationService $createNavRecordFromCalculationService,
    ): JsonResponse {
        $validated = $request->validate([
            'valuation_date' => ['required', 'date'],
        ]);

        $snapshot = $calculatePlanNavService->calculateAndStore(
            plan: $plan,
            valuationDate: $validated['valuation_date'],
            createdBy: $request->user()?->id,
        );

        $navRecord = $createNavRecordFromCalculationService->create(
            plan: $plan,
            calculationData: [
                'valuation_date' => $snapshot->valuation_date->toDateString(),
                'nav_per_unit' => (float) $snapshot->nav_per_unit,
                'net_asset_value' => (float) $snapshot->net_asset_value,
                'outstanding_units' => (float) $snapshot->outstanding_units,
                'price_source' => $snapshot->price_source,
                'breakdown' => $snapshot->breakdown ?? [],
            ],
            createdBy: $request->user()?->id,
            notes: 'Created from NAV calculation.',
        );

        return response()->json([
            'message' => 'Plan NAV calculated and NAV record created successfully.',
            'data' => [
                'snapshot' => $snapshot,
                'nav_record' => $navRecord,
            ],
        ]);
    }

    public function acceptPreview(
        Request $request,
        Plan $plan,
        PreviewPlanNavService $previewPlanNavService,
        CreateNavRecordFromCalculationService $createNavRecordFromCalculationService
    ): JsonResponse {
        $validated = $request->validate([
            'valuation_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $preview = $previewPlanNavService->preview(
            plan: $plan,
            valuationDate: $validated['valuation_date'],
        );

        $navRecord = $createNavRecordFromCalculationService->create(
            plan: $plan,
            calculationData: $preview,
            createdBy: $request->user()?->id,
            notes: $validated['notes'] ?? 'Created from NAV calculation preview.',
        );

        return response()->json([
            'message' => 'NAV record created from calculation preview successfully.',
            'data' => $navRecord,
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