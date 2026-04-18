<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\PlanCashPosition;
use App\Http\Requests\Plan\StorePlanCashPositionRequest;
use App\Http\Requests\Plan\UpdatePlanCashPositionRequest;

class PlanCashPositionController extends Controller
{
    public function index(Plan $plan): JsonResponse
    {
        $rows = $plan->cashPositions()
            ->latest('position_date')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Plan cash positions retrieved successfully.',
            'data' => $rows,
        ]);
    }

    public function store(StorePlanCashPositionRequest $request, Plan $plan): JsonResponse
    {
        $position = PlanCashPosition::query()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'position_date' => $request->input('position_date'),
            'cash_amount' => $request->input('cash_amount'),
            'source_type' => $request->input('source_type', 'manual'),
            'notes' => $request->input('notes'),
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Plan cash position created successfully.',
            'data' => $position,
        ], 201);
    }

    public function update(
        UpdatePlanCashPositionRequest $request,
        Plan $plan,
        PlanCashPosition $cashPosition
    ): JsonResponse {
        abort_unless((int) $cashPosition->plan_id === (int) $plan->id, 404);

        $cashPosition->update($request->validated());

        return response()->json([
            'message' => 'Plan cash position updated successfully.',
            'data' => $cashPosition->fresh(),
        ]);
    }

    public function destroy(Plan $plan, PlanCashPosition $cashPosition): JsonResponse
    {
        abort_unless((int) $cashPosition->plan_id === (int) $plan->id, 404);

        $cashPosition->delete();

        return response()->json([
            'message' => 'Plan cash position deleted successfully.',
        ]);
    }
}