<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\PlanEquityHolding;
use App\Http\Requests\Plan\StorePlanEquityHoldingRequest;

class PlanEquityHoldingController extends Controller
{
    public function index(Plan $plan): JsonResponse
    {
        $rows = $plan->equityHoldings()
            ->with('marketSecurity')
            ->latest('trade_date')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Plan equity holdings retrieved successfully.',
            'data' => $rows,
        ]);
    }

    public function store(StorePlanEquityHoldingRequest $request, Plan $plan): JsonResponse
    {
        $holding = PlanEquityHolding::query()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'market_security_id' => $request->integer('market_security_id'),
            'quantity' => $request->input('quantity'),
            'invested_amount' => $request->input('invested_amount'),
            'average_cost_per_share' => $request->input('average_cost_per_share')
                ?: (
                    (float) $request->input('quantity') > 0
                        ? round((float) $request->input('invested_amount') / (float) $request->input('quantity'), 6)
                        : 0
                ),
            'trade_date' => $request->input('trade_date'),
            'holding_status' => $request->input('holding_status', 'active'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Plan equity holding created successfully.',
            'data' => $holding->load('marketSecurity'),
        ], 201);
    }
}