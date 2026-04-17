<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\PlanCashPosition;
use App\Http\Requests\Plan\StorePlanCashPositionRequest;

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
}